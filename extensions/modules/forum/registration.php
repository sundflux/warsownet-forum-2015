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
 * 2011 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2011
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
 * Registration module
 * @package       Module
 * @subpackage    Login
 */

if(!defined('SPAM_VERIFY_USERNAME')) DEFINE('SPAM_VERIFY_USERNAME', '0');

class Module_Forum_Registration extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.waitforimages");

        Forum::init();
        $this->timer = new Common_Timer;
        if (isset($_SESSION["UserID"])) {
            Controller_Redirect::to("/");
        }
    }

    public function index()
    {
        $captcha = new Captcha;
        if (isset($_POST["register"])) {
            Security::requireSessionID();
            Security::verifyReferer();

            if (!Captcha::getCaptcha()) {
                $captcha->genCaptcha();
            }
            $this->view->captchaView = (string) $captcha;
            foreach ($_POST as $k=>$v) {
                $this->view->$k = $v;
            }

            // flood timer
            try {
                $this->timer->trigger();
            } catch (Exception $e) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError($e->getMessage());

                return;
            }

            if (!isset($_POST["captcha"]) || empty($_POST["captcha"]) || !Captcha::validate($_POST["captcha"])) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Could not validate captcha. Please try again");

                return;
            }

            if (empty($_POST["username"]) || Common_Validate::user(trim($_POST["username"]))) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Username empty or user already exists.");

                return;
            }

            /*if (!preg_match("/^[_a-zA-Z0-9-.@]+$/", trim($_POST["value"]))) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Username has unallowed characters.");

                return;
            }*/

            if ($_POST["username"] != Security::strip($_POST["username"])) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Malformed username. Please choose another one.");

                return;
            }

            if (empty($_POST["email"]) || !Common_Validate::email(trim($_POST["email"]))) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Email is required.");

                return;
            }

            if (trim($_POST["email"]) != trim($_POST["emailconfirm"])) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Emails do not match.");

                return;
            }

            // Antispam
            if (FORUM_ENABLE_ANTISPAM == 1) {

                // Check if IP is banned
                $val = new Antispam_IP(Auth::getClientIP());
                $v = (string) $val;
                if ((int) $v > 0) {
                    $captcha->genCaptcha();
                    $this->view->captchaView = (string) $captcha;
                    $this->ui->addError("This IP is flagged as a spam source by stopforumspam.com. Registration not allowed.");

                    return;
                }

                // Check if Email is banned
                $val = new Antispam_Email(trim($_POST["email"]));
                $v = (string) $val;

                // always ban yahoo
                if(strstr($_POST["email"], 'yahoo.')
                    || strstr($_POST["email"], 'ymail.')
                    || strstr($_POST["email"], 'yahoomail.')) {
                    $captcha->genCaptcha();
                    $this->view->captchaView = (string) $captcha;
                    $this->ui->addError("Registration from Yahoo mail is not allowed due unmanageable amount of spam, sorry! Please use another email provider.");

                    return;
                }

                if ((int) $v > 0) {
                    $captcha->genCaptcha();
                    $this->view->captchaView = (string) $captcha;
                    $this->ui->addError("This email is flagged as a spam source by stopforumspam.com. Registration not allowed.");

                    return;
                }

                // Check if Username is banned
                if (SPAM_VERIFY_USERNAME == 1) {
                    $val = new Antispam_Username(trim($_POST["username"]));
                    $v = (string) $val;
                    if ((int) $v > 0) {
                        $captcha->genCaptcha();
                        $this->view->captchaView = (string) $captcha;
                        $this->ui->addError("This Username is flagged as a spam source. Registration not allowed.");

                        return;
                    }
                }
            }

            $hash = md5($_POST["email"].time());

            // Special trigger to do something when activating the account
            $specialtrigger = 0;
            if (isset($_SESSION["SpecialTrigger"])) {
                $specialtrigger = 1;
            }

            $query = "
                INSERT INTO forum_registration (username,email,hash,specialtrigger,created)
                VALUES (?,?,?,?,NOW()) ";

            try {
                // Write temporary hash
                $stmt = db()->prepare($query);
                $stmt->set(trim($_POST["username"]))->set(trim($_POST["email"]))->set($hash)->set($specialtrigger);
                $stmt->execute();

                // Send welcome email
                $email = new Email;
                $email->to(trim($_POST["email"]), trim($_POST["email"]));
                $email->from(FORUM_SENDER_MAIL, FORUM_AUTHOR);
                $email->replyto(FORUM_SENDER_MAIL, FORUM_AUTHOR);
                $email->subject(FORUM_AUTHOR.", please confirm your registration.");
                    $letter = "Welcome to ".FORUM_AUTHOR.". Please finish your account registration here:";
                    $letter .= "\n\n";
                    $letter .= $this->request->getBaseUri()."forum_registration/complete/{$hash}\n\n";
                $email->body($letter);

                $email->sendEmail();
                Controller_Redirect::to("forum_registration/sent");
            } catch (Exception $e) {

            }
        } else {
            $captcha->genCaptcha();
            $this->view->captchaView = (string) $captcha;
        }
    }

    public function sent() { }

    public function complete()
    {
        if (!$this->request->getParam(0)) {
            throw new Exception("Hash not found");
        }

        $query = "
            SELECT username,email,hash,specialtrigger
            FROM forum_registration
            WHERE hash=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($this->request->getParam(0));
            $stmt->execute();
            $row = $stmt->fetch();
            if (!isset($row->email)) {
                throw new Exception("Hash not found");
            }

            $this->view->info = $row;
        } catch (Exception $e) {
            $this->ui->addError($e->getMessage());
            $this->ui->setPageRoot("empty");

            return;
        }
        $captcha = new Captcha;
        if (isset($_POST["create"])) {
            if (!Captcha::getCaptcha()) {
                $captcha->genCaptcha();
            }
            $this->view->captchaView = (string) $captcha;
            foreach ($_POST as $k=>$v) {
                $this->view->$k = $v;
            }

            if (!isset($_POST["captcha"]) || empty($_POST["captcha"]) || !Captcha::validate($_POST["captcha"])) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Could not validate captcha. Please try again");

                return;
            }

            if (trim($_POST["password"]) != trim($_POST["password2"]) || strlen($_POST["password"]) < 8) {
                $captcha->genCaptcha();
                $this->view->captchaView = (string) $captcha;
                $this->ui->addError("Meep! Passwords don't match or password too short. Minimum 8 characters!");

                return;
            }

            try {
                // All good, create user
                $user = new Forum_User;
                $user->username = $this->view->info->username;
                $user->password = $_POST["password"];
                $user->email = $this->view->info->email;
                $user->commit();

                // Delete from queue
                $query = "DELETE FROM forum_registration WHERE hash=?";
                $stmt = db()->prepare($query);
                $stmt->set($hash);
                $stmt->execute();
                $this->ui->addMessage("User account created! Have fun.");

                // send account info
                $email = new Email;
                $email->to(trim($this->view->info->email), trim($this->view->info->username));
                $email->from(FORUM_SENDER_MAIL, FORUM_AUTHOR);
                $email->replyto(FORUM_SENDER_MAIL, FORUM_AUTHOR);
                $email->subject("Welcome to ".FORUM_AUTHOR." forums!");
                    $letter = "Welcome to ".FORUM_AUTHOR." forums.";
                    $letter .= "\n\n";
                    $letter .= "Username: {$this->view->info->username}\n";
                    $letter .= $this->request->getBaseUri()."\n";
                $email->body($letter);
                $email->sendEmail();

                // attempt to login
                $login = new Auth_Login($user->username,$user->password);
                $login->login();

                // Redirect to main
                Controller_Redirect::to("/");
            } catch (Exception $e) {
                $this->ui->addError("Fatal error during account creation. " . $e->getMessage());
            }
        } else {
            $captcha->genCaptcha();
            $this->view->captchaView = (string) $captcha;
        }
    }

    /* ajax validation */

    public function validate()
    {
        if (!$this->request->isAjax()) {
            return;
        }

        // Post can have key/value, what we do with it depends on code below...
        if ((!isset($_POST["key"]) || empty($_POST["key"])) || (!isset($_POST["value"]) || empty($_POST["value"]))) {
            echo 'EXISTS';

            return;
        }

        // Validate something with this data:
        // "OK"      = validation ok
        // "EXISTS " = validation fail
        switch ($_POST["key"]) {
            case "user":
                // Check if user exists
                if (Common_Validate::user(trim($_POST["value"])) || strlen(trim($_POST["value"])) < 3) {
                    echo "EXISTS";
                    exit;
                }
                /*if (!preg_match("/^[a-zA-Z0-9-_.@]+$/", trim($_POST["value"]))) {
                    echo "EXISTS";
                    exit;
                }*/
                break;

            case "email":
                // Check if email exists
                if (!Common_Validate::email(trim($_POST["value"]))) {
                    echo "EXISTS";
                    exit;
                }
                break;

            case "emailconfirm":
                // Check if email exists
                if (empty($_POST["emailconfirm"]) || ($_POST["email"] != $_POST["emailconfirm"])) {
                    echo "EXISTS";
                    exit;
                }
                break;

            case "password":
                // Check that password is long enough
                if (strlen($_POST["value"]) < 8) {
                    echo 'EXISTS';
                    exit;
                }
                break;

            case "passwordmatch":
                if (trim($_POST["password"]) != trim($_POST["password2"]) || strlen($_POST["password"]) < 8) {
                    echo 'EXISTS';
                    exit;
                }
                break;

        }

        echo "OK";
        exit;
    }

}
