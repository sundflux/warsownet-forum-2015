<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1>Registration</h1>
		<form method="post" action="{/page/common/binding}?session_id={php:function('session_id')}">
			<label for="username">Username</label>
			<input type="text" name="username" value="{username}" id="username"/> &#160;
			<span id="check" class="padded hidden">Checking...</span>
			<span id="available" class="ok hidden">Username available!</span>
			<span id="notavailable" class="error hidden">This username is taken already.</span>
			
			<label for="email">Email</label>
			<input type="text" name="email" value="{email}" id="email"/> &#160;
			<span id="check-email" class="padded hidden">Checking...</span>
			<span id="available-email" class="ok hidden">Email OK!</span>
			<span id="notavailable-email" class="error hidden">Meep! Not a valid email</span>
			
			<label for="emailconfirm">Confirm email</label>
			<input type="text" name="emailconfirm" value="{emailconfirm}" id="emailconfirm"/> &#160;
			<span id="check-confirm" class="padded hidden">Checking...</span>
			<span id="available-confirm" class="ok hidden">Emails match</span>
			<span id="notavailable-confirm" class="error hidden">Meep! Emails don't match!</span>

			<label for="captchaView">Are you human?</label>
			<br/>
			<div id="captcha">
				<img src="{captchaView}" alt=""/>
			</div>
			<br/>
			Type the letters above here:<br/>
			<input type="text" size="4" name="captcha" value=""/>

			<label for="register">&#160;</label>
			<input type="submit" name="register" value="Register"/>
		</form>
	</xsl:template>

	<xsl:template match="sent">
		<h1>Confirmation email sent</h1> 
		<p>Please check your mailbox for confirmation email and follow the link in the mail to finish your account creation.</p>
	</xsl:template>

	<xsl:template match="empty">
	</xsl:template>

	<xsl:template match="complete">
		<h1>Complete your account registration</h1>
		<p>Hello <xsl:value-of select="info/username"/>, You're almost done! Choose password for your forum account:</p>
		<form method="post" action="{/page/common/binding}/complete/{info/hash}">
			<label for="password">Password:</label>
			<input type="password" id="password" name="password" value="{password}"/> &#160;
			<span id="check" class="padded hidden">Checking...</span>
			<span id="available" class="ok hidden">Password OK!</span>
			<span id="notavailable" class="error hidden">Meep! Password is too short.</span>
			
			<label for="password2">Confirm password:</label>
			<input type="password" id="password2" name="password2" value="{password2}"/> &#160;
			<span id="check-confirm" class="padded hidden">Checking...</span>
			<span id="available-confirm" class="ok hidden">Passwords match</span>
			<span id="notavailable-confirm" class="error hidden">Meep! Passwords don't match!</span>
			
			<br/>
			<label for="captchaView">Are you human?</label>
			<br/>
			<div id="captcha">
				<img src="{captchaView}" alt=""/>
			</div>
			Type the letters above here:<br/>
			<input type="text" size="4" name="captcha" value=""/>
			
			<label for="create">&#160;</label>
			<input type="submit" name="create" value="Create account"/>
		</form>
	</xsl:template>
	
</xsl:stylesheet>
