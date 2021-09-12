<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1><xsl:value-of select="php:function('i18n','Unread posts')"/></h1>
		
		<xsl:if test="unread/numposts &gt; 0">
			<form method="post" action="{/page/common/binding}/markAllRead?session_id={php:function('session_id')}">
				<input type="submit" name="submit" value="{php:function('i18n','Mark all posts as read')}" />
			</form>
		</xsl:if>		
		
		<xsl:if test="unread/numposts = '0'">
			<xsl:value-of select="php:function('i18n','No unread posts.')"/>
		</xsl:if>
		
		<xsl:for-each select="unread/forums">
			<h3><xsl:value-of select="forum"/>
			</h3>
			<div class="tbl">
				<div class="tbl-thead">
					<div class="tbl-row">
						<div class="topic topic-unread rounded-left"><xsl:value-of select="php:function('i18n','Topic')"/></div>
						<div class="lastpost rounded-right"><xsl:value-of select="php:function('i18n','Last post')"/></div>
					</div>
				</div>
				<div class="tbl-tbody">
					<xsl:for-each select="threads">
						<xsl:variable name="user"><xsl:value-of select="user_id"/></xsl:variable>
						<div class="tbl-row">
							<div class="tbl-cell topic">
								<a href="forum/thread/{topic_id}/1">
									<xsl:attribute name="topic_title">
										<xsl:value-of select="topic_title"/>
									</xsl:attribute>
									<xsl:value-of select="topic_title"/>
								</a>
							</div>
							<div class="tbl-cell lastpost"><a href="forum/thread/t/{last_post_id}#post-{last_post_id}"><span class="datetime"><xsl:value-of select="last_post"/></span></a>&#160;by&#160;<xsl:value-of select="../../users[user_id=$user]/username" disable-output-escaping="yes"/></div>
						</div>
					</xsl:for-each>
				</div>
			</div>
		</xsl:for-each>
	</xsl:template>	

</xsl:stylesheet>
