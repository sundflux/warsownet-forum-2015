<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1>Forgot your password?</h1>
		<p>To reset your password, type your username below. You will receive confirmation email which contains confirmation link to reset your password.</p>
		<form method="post" action="{/page/common/binding}?session_id={php:function('session_id')}" accept-charset="{/page/common/encoding}">
			<fieldset>
				<label for="username">Username</label>
				<input type="text" name="username" value="{username}"/>
				<br/>
				<br/>
				<label>&#160;</label>
				<input type="submit" name="continue" value="{php:function('i18n','Request new password')}"/>
				<br/>
			</fieldset>
		</form>
	</xsl:template>
	
	<xsl:template match="confirm">
		<h1>Confirmation email sent.</h1>
		<p>You will soon receive email to your email address (ending @<xsl:value-of select="email"/>) 
			which contains confirmation link to reset your password.</p>
	</xsl:template>
	
	<xsl:template match="reset">		
	</xsl:template>
	
	<xsl:template match="changed">
		<h1>Password reset</h1>
		<p>New password has been sent to your email.</p>
	</xsl:template>

</xsl:stylesheet>
