<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<xsl:if test="wait">
			<xsl:value-of select="wait"/>
		</xsl:if>
	
		<xsl:if test="notlogged">
			<div id="ajax-login-form">
				<xsl:value-of select="php:function('i18n','You must be logged in to post.')"/> <a href="forum_login" onclick="return Login.getForm();"><xsl:value-of select="php:function('i18n','login')"/></a>
			</div>
		</xsl:if>

		<xsl:if test="not(notlogged) and not(wait)">
			<xsl:choose>
				<xsl:when test="not(topic_id) and not(post_id)">
					<h1><xsl:value-of select="php:function('i18n','Post new topic')"/></h1> 	
				</xsl:when>
				<xsl:when test="post_id!=''">
					<h1><xsl:value-of select="php:function('i18n','Edit post')"/></h1> 					
				</xsl:when>
				<xsl:when test="topic_id and not(post_id)">
					<h1><xsl:value-of select="php:function('i18n','Reply to topic')"/></h1>		
				</xsl:when>
			</xsl:choose>			
			<form method="post" action="{/page/common/binding}/{forum_id}/{topic_id}?session_id={php:function('session_id')}"  accept-charset="{/page/common/encoding}">
				<xsl:choose>
					<xsl:when test="topic_id">
						<xsl:attribute name="onsubmit">return Post.checkEmptyContent();</xsl:attribute>					
					</xsl:when>
					<xsl:otherwise>
						<xsl:attribute name="onsubmit">return Post.checkEmpty();</xsl:attribute>
					</xsl:otherwise>
				</xsl:choose>
				<input type="hidden" name="post_id" id="post_id" value="{post_id}"/>
				<!-- preview -->
				<div id="preview-view" style="display: none;">
					<xsl:if test="not(topic_id)"><h1><span id="title-preview" />&#160;</h1></xsl:if>

					<div class="tbl">
						<div class="tbl-thead">
							<div class="tbl-row">
								<div class="tbl-cell user rounded-left" style="width: 175px"><xsl:value-of select="php:function('i18n','Posted by')"/></div>
								<div class="tbl-cell content rounded-right">
									<xsl:value-of select="php:function('i18n','Post')"/> 
								</div>
							</div>
						</div>
						<div class="tbl-tbody">
							<div class="tbl-row">
								<div class="tbl-cell user alt3" style="width: 175px">
									<xsl:value-of select="/page/common/user"/>
								</div>
								<div class="tbl-cell content heighted alt2">
									<div id="post-content-{post_id}">
										<div id="preview"></div>
									</div>
									<br class="clear"/>
								</div>
							</div>
						</div>
					</div>

					<br/>
					<xsl:choose>
						<xsl:when test="not(topic_id) and not(post_id)">
							<input type="submit" name="add" value="{php:function('i18n','Post topic')}"/>
						</xsl:when>
						<xsl:when test="post_id!=''">
							<input type="submit" name="add" value="{php:function('i18n','Save post')}"/>							
						</xsl:when>
						<xsl:when test="topic_id and not(post_id)">
							<input type="submit" name="add" value="{php:function('i18n','Reply to topic')}"/>				
						</xsl:when>
					</xsl:choose>
					<input type="button" id="button-edit" value="Edit" onclick="BBCode.togglePreview();"/>
				</div>		
				<!-- edit view -->
				<div id="edit-view">
					<input type="hidden" name="forum_id" value="{forum_id}"/>
					<!-- bbcode editor -->
					<fieldset>
						<xsl:if test="not(topic_id)">
							<label for="title"><xsl:value-of select="php:function('i18n','Topic title')"/></label>
							<input type="text" name="title" id="topic-title" class="topic-title" value="{title}" placeholder="{php:function('i18n','Topic title here')}"/>
							<br/>
						</xsl:if>
						<div class="btn bold" title="bold"></div><div class="btn italic"></div><div class="btn underline"></div><div class="btn link"></div><div class="btn quote"></div>
						<div class="btn code"></div><div class="btn image"></div><div class="btn back"></div><div class="btn forward"></div>		
						<br/>
						<textarea name="content" id="topic-content" class="topic-content" placeholder="{php:function('i18n','Your thoughts here')}"><xsl:value-of select="content"/></textarea>
						<br class="clear"/>

						<xsl:if test="notVerified">
							<div id="captcha">
								<img src="{captchaView}" alt=""/>
							</div>
							<xsl:value-of select="php:function('i18n','Type the letters above here:')"/><br/>
							<input type="text" size="4" name="captcha" value=""/>
						</xsl:if>

						<br class="clear"/>
						<xsl:choose>
							<xsl:when test="not(topic_id) and not(post_id)">
								<input type="submit" name="add" value="{php:function('i18n','Post topic')}"/>
							</xsl:when>
							<xsl:when test="post_id!=''">
								<input type="submit" name="add" value="{php:function('i18n','Save post')}"/>							
							</xsl:when>
							<xsl:when test="topic_id and not(post_id)">
								<input type="submit" name="add" value="{php:function('i18n','Reply to topic')}"/>				
							</xsl:when>
						</xsl:choose>
						<input type="button" id="button-preview" value="{php:function('i18n','Preview')}" onclick="BBCode.togglePreview();"/>
					</fieldset>
				</div>
			</form>
		</xsl:if>
		<script type="text/javascript">
			Forms.preventDouble();
		</script>
	</xsl:template>
	
	<xsl:template match="preview">
		<xsl:value-of select="preview" disable-output-escaping="yes"/>
	</xsl:template>

</xsl:stylesheet>
