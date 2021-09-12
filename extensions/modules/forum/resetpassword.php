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
 * Reset password module
 * @package       Module
 * @subpackage    Login
 */

class Module_Forum_ResetPassword extends Main
{
    public function __construct()
    {
        $this->timer = new Common_Timer;
        Forum::init();
    }

    public function index()
    {
        // Post flood timer
        try {
            $this->timer->trigger();
        } catch (Exception $e) {
            $this->view->wait = $e->getMessage();

            return;
        }

        // Ip flood timer
        $timestamp = 60 * 60 * 2; // 2hr
        $timestamp = time() - $timestamp;
        $ip = $_SERVER["REMOTE_ADDR"];
        $query = "
            SELECT COUNT(id) as count
            FROM forum_resetpassword
            WHERE ip = ?
            AND UNIX_TIMESTAMP(created) > ?";

        $stmt = db()->prepare($query);
        $stmt->set($ip);
        $stmt->set($timestamp);
        try {
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row->count > 2) {
                $this->ui->addError("Too many password reset requests from this IP.");

                return;
            }

        } catch (Exception $e) {
            return;
        }

        // Delete requests older than 8 hours
        $timestamp = 60 * 60 * 8; // 2hr
        $timestamp = time() - $timestamp;
        $query = "
            DELETE FROM forum_resetpassword
            WHERE UNIX_TIMESTAMP(created) < ?";

        $stmt = db()->prepare($query);
        $stmt->set($timestamp);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }

        // Send password reset mail if user exists
        if (isset($_POST["username"])) {
            Security::requireSessionID();
            Security::verifyReferer();

            $validate = new Common_Validate;
            if ($validate->user($_POST["username"])) {
                $this->sendConfirmation($_POST["username"]);
            } else {
                $this->ui->addError("User not found.");
            }
        }
    }

    public function confirm()
    {
        // Show only domain on confirm page to indicate
        // to what domain the mail was sent to.
        $mail = explode("@", $_SESSION["tmp_ResetEmail"]);
        $this->view->email = $mail[1];
    }

    /**
     * Sends confirmation email to reset password
     * @param string $email Target email
     */
    private function sendConfirmation($username)
    {
        // User
        $user = new Common_User;
        $userid = $user->getUserIDByName($username);

        // Check for ddos attempts for this user :P
        $query = "
            SELECT COUNT(id) as count
            FROM forum_resetpassword
            WHERE user_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set($userid);
        try {
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row->count > 2) {
                $this->ui->addError(i18n("Too many password reset requests from this user."));
                Controller_Redirect::to("forum_resetpassword");
            }

        } catch (Exception $e) {
            return;
        }

        // Get users email
        $query = "
            SELECT email
            FROM forum_profile
            WHERE user_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $userid);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            return;
        }
        $row = $stmt->fetch();
        $email = $row->email;

        // Generate reset code
        $reqtime = time();
        $resetcode = md5($email).md5($reqtime);
        $resetcode = md5($resetcode);

        try {
            // Write dah code to db
            $query ="
                INSERT INTO forum_resetpassword (user_id, resetcode, ip)
                VALUES (?,?,?)";

            $stmt = db()->prepare($query);
            $stmt->set($userid);
            $stmt->set($resetcode);
            $stmt->set($_SERVER["REMOTE_ADDR"]);
            $stmt->execute();

            // Send email
            $mailer = new Email;
            $mailer->to($email, $email);
            $mailer->subject(i18n(FORUM_AUTHOR." - confirm password reset request"));
            $mailer->body(i18n("To RESET your password, please visit:")."\n ".$this->request->getBaseUri()."forum_resetpassword/reset/{$resetcode}\n\n".i18n("If you received this email in error and did not ask for password reset, you can discard this email."));
            if (!$mailer->sendEmail()) {
                $this->ui->addError("Sending email failed. Please try again later and if the problem persists, contact our customer support.");

                return;
            }
            $_SESSION["tmp_ResetEmail"] = $email;

            Controller_Redirect::to("forum_resetpassword/confirm");
        } catch (Exception $e) {
            $this->ui->addError("Could not send reset request");
        }
    }

    /**
     * Resets password
     */
    public function reset()
    {
        $user = new Common_User;

        if (!$this->request->getParam(0)) {
            $this->ui->addError(i18n("Invalid reset request"));

            return;
        }
        try {
            // Get userid for reset request
            $query ="
                SELECT user_id
                FROM forum_resetpassword
                WHERE resetcode=?";

            $stmt = db()->prepare($query);
            $stmt->set($this->request->getParam(0))->execute();
            $row = $stmt->fetch();
            $user_id = $row->user_id;

            if ($user_id === false) {
                $this->ui->addError("Invalid reset request");

                return;
            }
            $username = $user->getUsernameByID($user_id);

            // Genereate new random password
            $newpass = time();
            $newpass = md5($newpass).rand(0,1000000);
            $newpass = md5($newpass);
            $newpass = substr($newpass,0,8);

            // Get users email
            $query = "
                SELECT email
                FROM forum_profile
                WHERE user_id = ?";

            $stmt = db()->prepare($query);
            $stmt->set((int) $user_id);
            try {
                $stmt->execute();
            } catch (Exception $e) {
                return;
            }
            $row = $stmt->fetch();
            $email = $row->email;

            // Update the password
            $auth = new Auth;
            $auth->updatePassword($username,$newpass);
            $user = new Common_User;

            $mailer = new Email;
            $mailer->to($email, $email);
            $mailer->subject(i18n(FORUM_AUTHOR." - password changed"));
            $mailer->body("New password for {$username} is {$newpass}");
            if (!$mailer->sendEmail()) {
                $this->ui->addError("Sending password email failed. Please try again later and if the problem persists, contact our customer support.");

                return;
            }

            // Remove password request
            $query = "
                DELETE FROM forum_resetpassword
                WHERE (resetcode = ? or user_id = ?)";

            $stmt = db()->prepare($query);
            $stmt->set($this->request->getParam(0))->set((int) $user_id)->execute();

            Controller_Redirect::to("forum_resetpassword/changed");
        } catch (Exception $e) {
            $this->ui->addError(i18n("Invalid reset request"));
        }
    }

    public function changed()
    {
    }

}
