import { error } from '@sveltejs/kit';

type ErrorInfo = {
    title: string;
    message: string;
};

const errors: Record<number, ErrorInfo> = {
    400: {
        title: 'An unreadable scroll',
        message: 'Your request could not be understood. Check your details and try again.'
    },
    401: {
        title: 'Identify yourself, adventurer',
        message: 'You must sign in before continuing your quest.'
    },
    403: {
        title: 'Proficiency required',
        message: 'You lack the required proficiency to enter this area. Ask the portal administrator to grant you access.'
    },
    404: {
        title: 'Uncharted territory',
        message: 'The page or resource you seek could not be found.'
    },
    405: {
        title: 'That action is forbidden here',
        message: 'This route does not support the action you attempted.'
    },
    409: {
        title: 'Your paths have crossed',
        message: 'Your request conflicts with something that already exists or has changed. Refresh and check your details.'
    },
    413: {
        title: 'Over your carrying capacity',
        message: 'Your upload is too large. Choose a smaller file and try again.'
    },
    415: {
        title: 'An unfamiliar artifact',
        message: 'This file or data format is not supported.'
    },
    422: {
        title: 'The spell is incomplete',
        message: 'Some of your details are invalid. Check the required fields and try again.'
    },
    429: {
        title: 'Take a short rest',
        message: 'You have made too many requests. Wait a moment before trying again.'
    },
    500: {
        title: 'A critical failure',
        message: 'Something went wrong within the portal. Please try again later.'
    },
    502: {
        title: 'A broken sending stone',
        message: 'The portal received an invalid response from another service.'
    },
    503: {
        title: 'The portal is resting',
        message: 'This service is temporarily unavailable. Please return later.'
    },
    504: {
        title: 'The sending spell expired',
        message: 'Another service took too long to respond. Please try again.'
    }
};

export function getErrorInfo(status: number): ErrorInfo {
    return errors[status] ?? {
        title: 'Your adventure hit a snag',
        message: 'An unexpected problem interrupted your quest.'
    };
}

export function raiseApiError(status: number): never {
    const safeStatus =
        Number.isInteger(status) && status >= 400 && status <= 599
            ? status
            : 502;

    error(safeStatus, getErrorInfo(safeStatus).message);
}