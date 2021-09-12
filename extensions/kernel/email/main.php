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
 * 2005 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2005
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
 * Class for sending email.
 *
 * Integrates PHPMailer
 *
 * @package       Kernel
 * @subpackage    Email
 */

if(!defined('EMAIL_DRIVER'))   DEFINE('EMAIL_DRIVER', 'mail');
if(!defined('EMAIL_HOST'))     DEFINE('EMAIL_HOST', 'localhost');
if(!defined('EMAIL_SMTPAUTH')) DEFINE('EMAIL_SMTPAUTH', false);
if(!defined('EMAIL_USERNAME')) DEFINE('EMAIL_USERNAME', false);
if(!defined('EMAIL_PASSWORD')) DEFINE('EMAIL_PASSWORD', false);
if(!defined('EMAIL_FROM'))     throw new Exception('EMAIL_FROM must be defined.');
if(!defined('EMAIL_FROMNAME')) throw new Exception('EMAIL_FROMNAME must be defined.');

class Email
{
    /**
     * Constructor.
     *
     * Creates Email_Mailer instance and looksup settings from config.
     *
     * @access public
     * @uses   Email_Mailer
     */
    public function __construct()
    {
        $this->mail = new Email_Mailer;
        if (EMAIL_DRIVER == "smtp") {
            $this->mail->isSMTP();
        }
        if (EMAIL_DRIVER == "sendmail") {
            $this->mail->IsSendmail();
        }
        if (EMAIL_DRIVER == "mail") {
            $this->mail->IsMail();
        }
        $this->mail->Host      = EMAIL_HOST;
        $this->mail->SMTPAuth  = EMAIL_SMTPAUTH;
        $this->mail->Username  = EMAIL_USERNAME;
        $this->mail->Password  = EMAIL_PASSWORD;
        $this->mail->From      = EMAIL_FROM;
        $this->mail->FromName  = EMAIL_FROMNAME;
        $this->mail->WordWrap  = 78;
        $this->mail->CharSet   = "utf-8";
        $this->mail->Encoding  = "quoted-printable";
    }

    /**
     * Add To-recipient to email.
     *
     * To add multiple To-recipients, just call to() method as many time as needed.
     *
     * @access public
     * @param string $email Recipient email-address
     * @param string $name  Recipient name (optional)
     */
    public function to($email, $name = "")
    {
        $this->mail->AddAddress($email, $name);
    }

    /**
     * Add Cc-recipient to email.
     *
     * To add multiple Cc-recipients, just call cc() method as many time as needed.
     *
     * @access public
     * @param string $email Recipient email-address
     * @param string $name  Recipient name (optional)
     */
    public function cc($email, $name = "")
    {
        $this->mail->AddCC($email, $name);
    }

    /**
     * Add Bcc-recipient to email.
     *
     * To add multiple Bcc-recipients, just call bcc() method as many time as needed.
     *
     * @access public
     * @param string $email Recipient email-address
     * @param string $name  Recipient name (optional)
     */
    public function bcc($email, $name = "")
    {
        $this->mail->AddBCC($email, $name);
    }

    /**
     * Set message sender (from:) address.
     *
     * @access public
     * @param string $email Sender email-address
     * @param string $name  Sender name (optional)
     */
    public function from($email, $name = "")
    {
        $this->mail->From = $email;
        $this->mail->FromName = $name;
    }

    /**
     * Add reply-to address to message.
     *
     * @access public
     * @param string $email Sender reply-to email-address
     * @param string $name  Sender name (optional)
     */
    public function replyto($email, $name = "")
    {
        $this->mail->AddReplyTo($email, $name);
    }

    /**
     * Adds an attachment to message.
     *
     * Requires path to file as parameter.
     *
     * @access public
     * @param  string $file Path to file
     * @return bool   True on success, false on error
     */
    public function attach($file = "", $encoding = "base64", $type = "application/octet-stream")
    {
        return $this->mail->AddAttachment($file, $name="", $encoding, $type);
    }

    /**
     * Set message subject.
     *
     * @access public
     * @param string $subj Subject
     */
    public function subject($subj)
    {
        $this->mail->Subject = $subj;
    }

    /**
     * Set message body.
     *
     * @access public
     * @param string $body Message body
     */
    public function body($body)
    {
        $this->mail->Body = $body;
    }

    /**
     * Returns possible error messages.
     *
     * @access public
     * @return string Error-message
     */
    public function getError()
    {
        return $this->mail->ErrorInfo;
    }

    /**
     * Send the message.
     *
     * @access public
     * @return bool
     */
    public function sendEmail()
    {
        return $this->mail->Send();
    }

}
