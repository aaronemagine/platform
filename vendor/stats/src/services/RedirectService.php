<?php
namespace Craft;

class craftstats_RedirectService extends BaseApplicationComponent
{
    public function redirectLoggedOutUsers()
    {
        // Check if the user is a guest (not logged in)
        if (craft()->userSession->isGuest()) {
            // Redirect to the login page
            craft()->request->redirect(UrlHelper::getUrl('login'));
        }
    }
}