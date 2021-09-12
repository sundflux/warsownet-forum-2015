<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1><xsl:value-of select="php:function('i18n','Bookmarks')"/></h1>
		
		<xsl:if test="bookmarksCount = '0'">
			You have no bookmarks.
		</xsl:if>

		<xsl:if test="bookmarksCount &gt; 0">
			<div class="tbl">
				<div class="tbl-thead">
					<div class="tbl-row">
						<div class="topic topic-unread rounded-left">Topic</div>
						<div class="lastpost rounded-right">Last post</div>
					</div>
				</div>
				<div class="tbl-tbody">
					<xsl:for-each select="bookmarks/threads">
						<xsl:variable name="user"><xsl:value-of select="last_post_by"/></xsl:variable>
						<div class="tbl-row">
							<div class="tbl-cell topic">
								<a href="forum_bookmark/delete/{topic_id}?session_id={php:function('session_id')}" title="Remove bookmark"><img src="/forum-images/icons/set/x_alt_12x12.png" alt="Remove bookmark"/></a>
								&#160;
								<a href="forum/thread/{topic_id}/1">
									<xsl:attribute name="topic_title">
										<xsl:value-of select="topic_title"/>
									</xsl:attribute>
									<xsl:value-of select="topic_title"/>
								</a>
							</div>
							<div class="tbl-cell lastpost">
								<a href="forum/thread/t/{last_post_id}#post-{last_post_id}">
									<span class="datetime"><xsl:value-of select="last_post"/></span>
								</a> by <xsl:value-of select="../users[user_id=$user]/username"/>								
							</div>
						</div>
					</xsl:for-each>
				</div>
			</div>
		</xsl:if>
		
		<br/>
		<xsl:if test="unread/numposts &gt; 0">
			<form method="get" action="{/page/common/binding}/markAllRead?session_id={php:function('session_id')}">
				<input type="submit" name="submit" value="{php:function('i18n','Mark all posts as read')}" />
			</form>
		</xsl:if>
	</xsl:template>	

</xsl:stylesheet>
