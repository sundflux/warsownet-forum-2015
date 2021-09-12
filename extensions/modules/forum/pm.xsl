<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<div id="discussion" class="padded">
			<div style="text-align: center;">(no discussion)</div>
		</div>
		<div id="discussions"></div>

		<xsl:if test="loadDiscussion">
			<script type="text/javascript">
				Discussion.discussion(<xsl:value-of select="loadDiscussion"/>);
				$('#discussion-messageForm').focus();
				Discussion.discussionToBottom();				
			</script>
		</xsl:if>
		
		<br class="c"/>
	</xsl:template>	

	<xsl:template match="discussions">
		<div class="discussion-user padded alt2 rounded" onclick="Discussion.newDiscussion();">
			<div class="discussion-user-avatar">
				<img src="/forum-images/icons/set/plus_16x16.png" alt="" width="16" height="16" style="margin-top: 6px; margin-left: 6px;"/>
			</div>
			<h3>Start new conversation</h3>
			<br/>
		</div>		
		<h4>Open conversations:</h4>
		<xsl:if test="discussions/discussions">
			<xsl:for-each select="discussions/discussions">
				<xsl:variable name="user"><xsl:value-of select="user_id"/></xsl:variable>
				<div class="discussion-user padded alt rounded" onclick="Discussion.discussion({$user});">
					<div class="discussion-user-avatar">
						<xsl:if test="../users[user_id=$user]/avatar!=''">
							<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='0'">
								<img src="{../../avatarurl}/{../users[user_id=$user]/avatar}" alt="" width="32" height="32"/>
							</xsl:if>
							<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='1'">
								<img src="http://www.gravatar.com/avatar/{../users[user_id=$user]/avatar}?s=32&amp;d=wavatar&amp;r=PG" alt=""/>
							</xsl:if>
							<br/>
						</xsl:if>
					</div>
					<h3><xsl:value-of select="../users[user_id=$user]/username"/></h3>

					<div class="discussion-archive">
						<form method="post" action="forum_pm/archive/{$user}?session_id={php:function('session_id')}">
							<input type="submit" name="archive" value="x"/>
						</form>
					</div>

					<xsl:if test="unreadCount &gt; 0">
						<div class="discussion-unread"><xsl:value-of select="unreadCount"/></div>
					</xsl:if>

					<br/>
				</div>
			</xsl:for-each>
		</xsl:if>
	</xsl:template>	

	<xsl:template match="discussion">
		<xsl:if test="discussion!=''">
			<xsl:for-each select="discussion">
				<div>
					<xsl:choose>
						<xsl:when test="../UserID = sender_id">
							<xsl:attribute name="class">discussion-message discussion-owner alt</xsl:attribute>
							<xsl:attribute name="style">float: left; margin-left: 48px;</xsl:attribute>
							<xsl:variable name="user"><xsl:value-of select="../UserID"/></xsl:variable>
							<div class="discussion-avatar-self alt">
								<div class="discussion-arrow-owner"/>
								<xsl:if test="../users[user_id=$user]/avatar!=''">
									<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='0'">
										<img src="{../avatarurl}/{../users[user_id=$user]/avatar}" alt="" width="32" height="32" style="z-index: 5"/>
									</xsl:if>
									<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='1'">
										<img src="http://www.gravatar.com/avatar/{../users[user_id=$user]/avatar}?s=32&amp;d=wavatar&amp;r=PG" alt="" style="z-index: 5" width="32" height="32"/>
									</xsl:if>
									<br/>
								</xsl:if>
							</div>							
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="class">discussion-message discussion-other alt2</xsl:attribute>
							<xsl:attribute name="style">float: right; margin-right: 48px;</xsl:attribute>
							<xsl:variable name="user"><xsl:value-of select="../targetUserID"/></xsl:variable>
							<div class="discussion-avatar-other alt2">
								<div class="discussion-arrow-other"/>
								<xsl:if test="../users[user_id=$user]/avatar!=''">
									<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='0'">
										<img src="{../avatarurl}/{../users[user_id=$user]/avatar}" alt="" width="32" height="32"/>
									</xsl:if>
									<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='1'">
										<img src="http://www.gravatar.com/avatar/{../users[user_id=$user]/avatar}?s=32&amp;d=wavatar&amp;r=PG" alt="" width="32" height="32"/>
									</xsl:if>
									<br/>
								</xsl:if>
							</div>							
						</xsl:otherwise>
					</xsl:choose>			
					<span class="discussion-message-title">
						<xsl:choose>
							<xsl:when test="../UserID = sender_id">
								<xsl:variable name="user"><xsl:value-of select="../UserID"/></xsl:variable>
								<span class="datetime"><xsl:value-of select="sent_date"/></span>&#160;<xsl:value-of select="../users[user_id=$user]/username"/> wrote:
							</xsl:when>
							<xsl:otherwise>
								<xsl:variable name="user"><xsl:value-of select="../targetUserID"/></xsl:variable>
								<span class="datetime"><xsl:value-of select="sent_date"/></span>&#160;<xsl:value-of select="../users[user_id=$user]/username"/> wrote:
							</xsl:otherwise>
						</xsl:choose>	
					</span>
					<br/>
					<xsl:value-of select="message" disable-output-escaping="yes"/>
					<br/>
				</div>
				<br/>
			</xsl:for-each>

			<hr class="nicehr"/>
		</xsl:if>

		<br/>
<!--		<div id="#btm"/>-->
		<div id="discussion-messageForm-loader">
			<xsl:call-template name="message"/>
		</div>

<!--		<div id="discussion-messageForm-loader"/>
		<br/>
		<br/>

		<script type="text/javascript">
			Discussion.fetch('/forum_pm/message/<xsl:value-of select="targetUserID"/>','#discussion-messageForm-loader');
		</script>-->
	</xsl:template>

	<xsl:template name="message">
		<div id="discussion-messageForm" class="alt3 padded rounded">
			<form method="post" action="/forum_pm/add?session_id={php:function('session_id')}">
				<input type="hidden" name="targetUserID" value="{targetUserID}" />
				<textarea name="message"></textarea><br/>
				<input type="submit" name="submit" value="Send message" id="discussion-messageForm-submit" class="l"/>
				<span class="l">(ctrl-enter sends the message)</span>
			</form>
			<br/>
		</div>
		<br/>
		<script type="text/javascript">
			Discussion.initializeSubmitOnEnter();
			$('#discussion-messageForm').focus();
			Discussion.discussionToBottom();
		</script>
	</xsl:template>

	<xsl:template match="message">
		<div id="discussion-messageForm-loader">
			<xsl:call-template name="message"/>
		</div>
	</xsl:template>

	<xsl:template match="start">
		<div class="padded">
			<div class="padded rounded">
				<form method="get" action="/forum_pm/start/search/1">
					<b>Search users:</b>
					<br/>
					<input type="text" name="s" style="height:22px;" value="{s}"/>
					&#160;
					<input type="submit" name="submit" value="Search users" style="height:32px;"/>
				</form>
				<br/>
				<xsl:if test="s">
					<h3>Did you mean...</h3>

					<dl>
						<xsl:for-each select="users/users">
							<dd>
								<a href="/forum_pm?d={user_id}"><xsl:value-of select="username"/></a>
							</dd>
						</xsl:for-each>
						<dd>&#160;</dd>
					</dl>				
					<br/>
					<div class="pagination l">
						<xsl:call-template name="pagination">
							<xsl:with-param name="url"><xsl:value-of select="users/url"/></xsl:with-param>
							<xsl:with-param name="onlyButtons">0</xsl:with-param>					
							<xsl:with-param name="urlAppend"><xsl:if test="s!=''">?s=<xsl:value-of select="s"/></xsl:if></xsl:with-param>
							<xsl:with-param name="pageCurrent"><xsl:value-of select="users/pages/page"/></xsl:with-param>
							<xsl:with-param name="pageNext"><xsl:value-of select="users/pages/pageNext"/></xsl:with-param>
							<xsl:with-param name="pagePrev"><xsl:value-of select="users/pages/pagePrev"/></xsl:with-param>
							<xsl:with-param name="pageCount"><xsl:value-of select="users/pages/pages"/></xsl:with-param>
						</xsl:call-template>			
					</div>					
				</xsl:if>
				<br/>
			</div>
		</div>
		<br/>
	</xsl:template>	

</xsl:stylesheet>
