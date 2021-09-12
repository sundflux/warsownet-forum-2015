<?php
/**
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Copyright (C)
 * 2004,2005,2006,2007,2011 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2004,2005,2006,2007,2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 */

/**
 * Login module
 * @package       Module
 * @subpackage    Login
 */
class Module_Forum_Login extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.waitforimages");

        Forum::init();
    }

    public function index()
    {
        if ($this->request->isAjax())
            $this->view->isAjax = true;

        if (isset($_SESSION["UserID"]) && !empty($_SESSION["UserID"])) {
            Controller_Redirect::to(LIBVALOA_DEFAULT_ROUTE_AUTHED);
        }

        $this->view->show_steam = 0;
        if (defined('LOGIN_SHOW_STEAM')) {
            $this->view->show_steam = LOGIN_SHOW_STEAM;
        }
    }

    public function login()
    {
        if (isset($_POST["user"]) && isset($_POST["pass"])) {
            Security::requireSessionID();
            Security::verifyReferer();

            try {
                $_SESSION["AuthComplete"] = 0;

                // attempt to login
                $login = new Auth_Login($_POST["user"], $_POST["pass"]);
                $login->login();

                // Check for bans
                Forum_Login::checkBan();

                // Check for verified status
                Forum_Login::checkVerified();

                // Load user permissions to session
                Forum_Login::loadAccessGroups();

                // Autologin/remember me
                if (isset($_POST["remember"]) && !empty($_POST["remember"])) {
                    $user = new Common_User;

                    // Use old password hash if found
                    $hash = $user->getSetting("AutologinHash", $_POST["user"]);
                    if (empty($hash) || $hash == false) {
                        $hash = md5(time() . $_POST["pass"]);
                    }

                    $user->setSetting("AutologinHash", $hash);
                    $user->setSetting("AutologinExpire", time() + (3600 * 24 * 14)); // 14 days
                    setcookie("ForumAutologin", "{$_POST["user"]};{$hash}", time() + (3600 * 24 * 14), $this->request->getPath());
                }

                // Update unread posts
                $unread = new Forum_Unread;
                $unread->get();

                $_SESSION["AuthComplete"] = 1;

                if ($this->request->isAjax()) {
                    $this->ui->setPageRoot("loginok");

                    return;
                }

                $this->index();
            } catch (Exception $e) {
                if ($this->request->isAjax()) {
                    $this->ui->setPageRoot("loginfailed");

                    return;
                }
                $this->ui->addError(i18n("Login failed, please check your username and password."));
                $this->ui->setPageRoot("index");
            }
        }
        if ($this->request->isAjax()) {
            $this->ui->setPageRoot("loginfailed");
        }
    }

    public function steam()
    {
        $steamurl = 'http://steamcommunity.com/openid/id/';

        // Include openid
        new OpenID;

        $openid = new LightOpenID('http://' . $_SERVER["SERVER_NAME"] . '/forum_login/steam');

        if (!$openid->mode) {
            // Attempt to login
            if (isset($_GET['login'])) {
                $openid->identity = 'https://steamcommunity.com/openid/';
                header('Location: ' . $openid->authUrl());
                exit;
            }
        } elseif ($openid->mode == 'cancel') {
            // User cancelled auth
            $this->view->cancel = true;
        } else {
            // Alles gut
            if ($openid->validate()) {
                $this->view->success = true;
                $this->view->user = str_replace($steamurl, '', $openid->identity);

                $this->createSSOAccount();
            } else {
                // Something went wrong
                $this->view->cancel = true;
            }
        }
    }

    public function createSSOAccount()
    {
        try {
            $user = new Forum_User;
            $user->username = 'steam_' . $this->view->user;
            $user->commit();
        } catch (Exception $e) {
            // Just ignore if account was already created.
        }

        try {
            $_SESSION["AuthComplete"] = 0;

            $auth = new Auth;
            $auth->setAuthenticationDriver('Steam');
            if ($auth->authenticate('steam_' . $this->view->user, $this->view->success)) {
                // Check for bans
                Forum_Login::checkBan();

                // Check for verified status
                Forum_Login::checkVerified();

                // Load user permissions to session
                Forum_Login::loadAccessGroups();

                // Update unread posts
                $unread = new Forum_Unread;
                $unread->get();

                // Get steam profile
                $steamProfileUrl = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.STEAM_KEY.'&steamids='.$this->view->user;

                $steamJSON = file_get_contents($steamProfileUrl);
                $steamJSON = json_decode($steamJSON);
                $_SESSION["__STEAM"] = $steamJSON;

                // Steam name and avatar
                $steamName = (string) $_SESSION['__STEAM']->response->players[0]->personaname;
                $steamAvatar = (string) $_SESSION['__STEAM']->response->players[0]->avatarmedium;

                $user = new Forum_User($_SESSION["UserID"]);
                $user->alias = $steamName;
                $user->avatar = $steamAvatar;
                $user->commit();

                $_SESSION["AuthComplete"] = 1;

                if ($this->request->isAjax()) {
                    $this->ui->setPageRoot("loginok");

                    return;
                }

                $this->index();
            } else {
                throw new Exception('Error');
            }
        } catch (Exception $e) {
            if ($this->request->isAjax()) {
                $this->ui->setPageRoot("loginfailed");

                return;
            }
            $this->ui->addError(i18n("Login failed, please check your username and password."));
            $this->ui->setPageRoot("index");
        }
    }

}
