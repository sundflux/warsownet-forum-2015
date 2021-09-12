<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1><xsl:value-of select="php:function('i18n','Change password')"/></h1>
		<form method="post" action="{/page/common/binding}/change?session_id={php:function('session_id')}" accept-charset="{/page/common/encoding}">
			<fieldset>
				<label for="oldpass"><xsl:value-of select="php:function('i18n','Old password')"/></label>
				<input type="password" name="oldpass" value="{oldpass}" id="oldpass"/><br/>
				<label for="newpass"><xsl:value-of select="php:function('i18n','New password')"/></label>
				<input type="password" name="newpass" value="{newpass}" id="newpass"/><br/>
				<label for="newpass2"><xsl:value-of select="php:function('i18n','Repeat new password')"/></label>
				<input type="password" name="newpass2" value="{newpass2}" id="newpass2"/><br/>
				<label>&#160;</label>
				<input type="submit" value="{php:function('i18n','Change')}"/>
			</fieldset>
		</form>
	</xsl:template>

	<xsl:template match="not_supported">
		<h1><xsl:value-of select="php:function('i18n','Change password')"/></h1>
		<p><xsl:value-of select="php:function('i18n','Changing password is not possible since this installation is using external authentication service.')"/></p>		
	</xsl:template>

</xsl:stylesheet>
