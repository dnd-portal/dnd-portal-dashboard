export type Permissions = {
    // ==================================
    //      Common admin permissions
    // ==================================
    // Admin wiki
    adminCanVisitAdminWiki  :boolean; // Default = False
    
    // Admin users
    adminCanVisitUsersPage  :boolean; // Default = False
    adminCanVisitUserPage   :boolean; // Default = False
    adminCanEditUser        :boolean; // Default = False
    adminCanDeleteUser      :boolean; // Default = False
    adminCanUpdateUser      :boolean; // Default = False

    // Other permissions
    adminCanVisitAdmin      :boolean; // Default = False




    // ==================================
    //      Common user permissions
    // ==================================
    // Profile
    userCanVisitProfile     :boolean; // Default = True
    userCanUpdateUsername   :boolean; // Default = False
    userCanUpdateEmail      :boolean; // Default = False
    userCanUpdatePassword   :boolean; // Default = False
    userCanRemoveAccount    :boolean; // Default = False

};

export type Permission = keyof Permissions;