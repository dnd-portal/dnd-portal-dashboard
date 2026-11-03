import assert from 'node:assert/strict';
import test from 'node:test';

const { AdminDataRequestError, getAdminData } = await import('../src/lib/admin/loadAdminData.ts');

const admin = {
    id: 1,
    display_name: 'Portal admin',
    email: 'admin@example.test',
    username: 'v4admin',
    account_status: 'active',
    roles: ['portal_admin']
};

const campaigns = [{ id: 1, name: 'Campaign', slug: 'campaign', type: 'campaign', location: null }];
const users = [admin];

function response(data, status = 200) {
    return new Response(JSON.stringify(status === 200 ? { data } : { error: { message: 'Not allowed.' } }), {
        status,
        headers: { 'content-type': 'application/json' }
    });
}

test('admin loader stops after auth 401 and leaves protected fan-out untouched', async () => {
    const requests = [];
    const fetcher = async (path) => {
        requests.push(path);
        return response(null, 401);
    };

    await assert.rejects(
        () => getAdminData(fetcher),
        (error) => {
            assert(error instanceof AdminDataRequestError);
            assert.equal(error.path, '/api/v1/auth/me');
            assert.equal(error.status, 401);
            return true;
        }
    );
    assert.deepEqual(requests, ['/api/v1/auth/me']);
});

for (const [description, body] of [
    ['an HTML body', '<!doctype html><html><body>Unauthorized</body></html>'],
    ['an empty body', '']
]) {
    test(`admin loader handles auth 401 with ${description} without fan-out`, async () => {
        const requests = [];
        const fetcher = async (path) => {
            requests.push(path);
            return new Response(body, { status: 401, headers: { 'content-type': 'text/html' } });
        };

        await assert.rejects(
            () => getAdminData(fetcher),
            (error) => {
                assert(error instanceof AdminDataRequestError);
                assert.equal(error.path, '/api/v1/auth/me');
                assert.equal(error.status, 401);
                return true;
            }
        );
        assert.deepEqual(requests, ['/api/v1/auth/me']);
    });
}

test('admin loader loads the authenticated admin data with event.fetch', async () => {
    const requests = [];
    const fetcher = async (path) => {
        requests.push(path);
        if (path === '/api/v1/auth/me') return response(admin);
        if (path === '/api/v1/users') return response(users);
        if (path === '/api/v1/campaigns') return response(campaigns);
        if (path.endsWith('/members') || path.endsWith('/sessions') || path.endsWith('/polls')) return response([]);
        if (path.endsWith('/payment-requests')) return response([]);
        if (path === '/api/v1/dashboard') return response({ admin: { activity: [] } });
        throw new Error(`Unexpected request: ${path}`);
    };

    const data = await getAdminData(fetcher);
    assert.equal(data.user.id, admin.id);
    assert.deepEqual(
        data.campaigns,
        campaigns.map((campaign) => ({ ...campaign, location: '' }))
    );
    assert.deepEqual(requests.sort(), [
        '/api/v1/auth/me',
        '/api/v1/campaigns',
        '/api/v1/campaigns/1/members',
        '/api/v1/campaigns/1/payment-requests',
        '/api/v1/campaigns/1/polls',
        '/api/v1/campaigns/1/sessions',
        '/api/v1/dashboard',
        '/api/v1/users'
    ]);
});

test('non-admin data remains non-admin and protected follow-up denial is surfaced', async () => {
    const player = { ...admin, roles: ['campaign_member'] };
    const requests = [];
    const fetcher = async (path) => {
        requests.push(path);
        if (path === '/api/v1/auth/me') return response(player);
        return response(null, 403);
    };

    await assert.rejects(
        () => getAdminData(fetcher),
        (error) => {
            assert(error instanceof AdminDataRequestError);
            assert.equal(error.status, 403);
            assert.equal(error.path, '/api/v1/users');
            return true;
        }
    );
    assert.equal(requests[0], '/api/v1/auth/me');
    assert.deepEqual(requests.slice(1).sort(), ['/api/v1/campaigns', '/api/v1/users']);
});

test('auth success followed by a 401 is surfaced once without retrying auth', async () => {
    const requests = [];
    const fetcher = async (path) => {
        requests.push(path);
        if (path === '/api/v1/auth/me') return response(admin);
        return response(null, 401);
    };

    await assert.rejects(
        () => getAdminData(fetcher),
        (error) => {
            assert(error instanceof AdminDataRequestError);
            assert.equal(error.status, 401);
            return true;
        }
    );
    assert.equal(requests.filter((path) => path === '/api/v1/auth/me').length, 1);
});
