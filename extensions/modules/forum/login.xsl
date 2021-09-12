<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1><xsl:value-of select="php:function('i18n','Login')"/></h1>
		<form method="post" action="{/page/common/binding}/login?ref={ref}&amp;session_id={php:function('session_id')}" accept-charset="{/page/common/encoding}">
			<fieldset>
				<label for="user"><xsl:value-of select="php:function('i18n','Username')"/></label>
				<input type="text" name="user" id="user" class="autofocus"/>
				<label for="pass"><xsl:value-of select="php:function('i18n','Password')"/></label>
				<input type="password" name="pass" id="pass"/>
				<label for="remember"><xsl:value-of select="php:function('i18n','Remember me')"/></label>				
				<input type="checkbox" name="remember" id="remember"/>
				<label>&#160;</label>
				<input type="submit" name="eventLogin" id="eventLogin" value="{php:function('i18n','Login')}">
					<xsl:if test="isAjax">
						<xsl:attribute name="onclick">
							return Login.submitForm(<xsl:value-of select="php:function('session_id')"/>);
						</xsl:attribute>
					</xsl:if>
				</input>
			</fieldset>
		</form>

		<p><a href="forum_resetpassword"><xsl:value-of select="php:function('i18n','Forgot your password?')"/></a></p>
        <xsl:if test="show_steam = '1'">        
	        <p><a href="{/page/common/binding}/steam?login"><img src="/site-images/ext/steamlogin.png" alt="Sign in through steam"/></a></p>
		</xsl:if>
	</xsl:template>
        
	<xsl:template match="steam">
            <xsl:choose>
                <xsl:when test="success">
                    <h1>Welcome Steam user number <xsl:value-of select="user"/>!</h1>
                </xsl:when>
                <xsl:when test="cancel">
                    The authentication was cancelled.
                </xsl:when>
                <xsl:otherwise>
                    
                </xsl:otherwise>
            </xsl:choose>
	</xsl:template>        
	
	<xsl:template match="loggedin">
		<h3><xsl:value-of select="php:function('i18n','Welcome')"/>, <xsl:value-of select="/page/common/user"/></h3>
	</xsl:template>

	<xsl:template match="loginok">
		<p><xsl:value-of select="php:function('i18n','Login successful')"/></p>
		<script type="text/javascript">
			$("#post").html('');
			Post.getForm('new-topic');
		</script>
	</xsl:template>

	<xsl:template match="loginfailed">
		<p><xsl:value-of select="php:function('i18n','Login failed.')"/> <a href="forum_login" onclick="return Login.getForm();"><xsl:value-of select="php:function('i18n','Try again.')"/></a></p>
	</xsl:template>
	
</xsl:stylesheet>
