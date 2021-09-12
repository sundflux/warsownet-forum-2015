<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">	
		<!-- forum administration -->
		<xsl:if test="forum_id != '-1' and topic_id = '-1'">
			<h3>Forum actions</h3>
			<dl>
				<dd><a href="forum/{forum_id}?e=t&amp;session_id={php:function('session_id')}">Rename</a></dd>
			</dl>
		</xsl:if>
		
		<!-- thread administration -->
		<xsl:if test="topic_id!='' and topic_id!='-1'">
			<h3>Topic actions</h3>
			<dl>
				<dd>
					<!-- rename -->
					<a href="forum/thread/{topic_id}?e=t">Rename</a>
				</dd>
				<dd>
					<!-- make sticky -->
					<xsl:choose>
						<xsl:when test="topic/pinned = '1'">
							<a href="forum_admin/unsticky/{topic_id}/{forum_id}?session_id={php:function('session_id')}">Unsticky topic</a>
						</xsl:when>
						<xsl:otherwise>
							<a href="forum_admin/sticky/{topic_id}/{forum_id}?session_id={php:function('session_id')}">Sticky topic</a>
						</xsl:otherwise>
					</xsl:choose>
				</dd>
				<dd>
					<!-- close topic -->
					<xsl:choose>
						<xsl:when test="topic/closed = '1'">
							<a href="forum_admin/opentopic/{topic_id}/{forum_id}?session_id={php:function('session_id')}">Open topic</a>
						</xsl:when>
						<xsl:otherwise>
							<a href="forum_admin/closetopic/{topic_id}/{forum_id}?session_id={php:function('session_id')}">Close topic</a>
						</xsl:otherwise>
					</xsl:choose>
				</dd>
				<dd>
					<!-- mark as spam -->
					<a onclick="if(!confirm('Sure?')) return false;" href="forum_admin/topicAsSpam/{topic_id}/{forum_id}/{page}?session_id={php:function('session_id')}">Mark as spam</a>			
				</dd>
				<dd>
					<!-- mark as spam -->
					<a onclick="if(!confirm('Sure?')) return false;" href="forum_admin/topicAsSpamWithBan/{topic_id}/{forum_id}/{page}?session_id={php:function('session_id')}">Mark as spam + ban original poster</a>			
				</dd>
				<dd>
					<!-- move topic -->
					<form method="post" action="forum_admin/movetopic/{topic_id}?session_id={php:function('session_id')}">
						Move to
						<select name="moveto">
							<xsl:for-each select="groups">
								<optgroup label="{group}">
									<xsl:for-each select="forums">
										<option value="{id}">
											<xsl:if test="../../forum_id = id">
												<xsl:attribute name="selected">selected</xsl:attribute>
											</xsl:if>
											<xsl:value-of select="name"/>
										</option>
									</xsl:for-each>
								</optgroup>
							</xsl:for-each>
						</select>
						&#160;
						<input type="submit" name="move" value="Move"/>
					</form>
				</dd>
			</dl>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="actions">	
		<h3>Forum administration</h3>		
		<h4>Forums</h4>
		<dl>
			<dd><a href="forum_admin/forums">Forums</a></dd>
		</dl>		
		<h4>Users</h4>
		<dl>
			<dd><a href="forum_admin/users">Users</a></dd>
			<dd><a href="forum_admin/access">Groups</a></dd>
		</dl>
	</xsl:template>

	<xsl:template match="access">	
		<form method="get" action="forum"><input type="submit" value="&#171; back to forum"/></form>	
		<!-- preloading placeholder -->
		<xsl:if test="preload">
			<div id="preload" style="display: none"><xsl:value-of select="preload"/></div>
		</xsl:if>		
	
		<div class="cell-50" style="float: left;">
			<h3>Groups</h3>
			<dl>
				<xsl:for-each select="groups">
					<dd>
						<xsl:choose>
							<xsl:when test="noedit">
								<xsl:value-of select="name"/>
							</xsl:when>
							<xsl:otherwise>
								<a href="javascript:;" onclick="Forms.fetch('/forum_admin/accessgroup/{id}','#forumadm-content',false);"><xsl:value-of select="name"/></a>
							</xsl:otherwise>
						</xsl:choose>
					</dd>
				</xsl:for-each>
				<dd>&#160;</dd>
				<dd id="add-button"><input type="button" value="add group" onclick="$('#add-button').toggle();$('#add').toggle();"/></dd>
				<dd style="display: none" id="add">
					<form method="post" action="forum_admin/addgroup">
						<input type="text" name="groupname" value=""/>
						&#160;
						<input type="submit" value="Add"/>
						&#160;
						<input type="button" value="Cancel" onclick="$('#add-button').toggle();$('#add').toggle();"/>
					</form>
				</dd>				
			</dl>
		</div>
		<div class="cell-50"  style="float: left;" id="forumadm-content">
			(select group)
		</div>		
	</xsl:template>	
	
	<xsl:template match="accessgroup">	
		<h3><xsl:value-of select="group/info/name"/></h3>
		<p>This group has access to following forum groups:</p>
		<form method="post" action="forum_admin/saveaccessgroup/{group/info/id}">
			<dl>
				<xsl:for-each select="group/access">
					<dd>
						<xsl:choose>
							<!-- public group. always has access -->
							<xsl:when test="public = '1'">
								<input name="forum_group[]" value="{id}" type="checkbox" checked="checked" /><!--disabled="disabled"-->
							</xsl:when>
							<!-- non-public group but has access -->
							<xsl:when test="public != '1' and access = '1'">
								<input name="forum_group[]" value="{id}" type="checkbox" checked="checked"/>
							</xsl:when>
							<!-- no access -->
							<xsl:when test="public != '1' and not(access)">
								<input name="forum_group[]" value="{id}" type="checkbox"/>
							</xsl:when>
						</xsl:choose>
						&#160;
						<xsl:value-of select="name"/>
					 	<br/>
						<blockquote>
							<small>
								<input type="radio" name="forum_access[{id}]" value="7">
									<xsl:choose>
										<xsl:when test="public = '1'">
											<xsl:attribute name="checked">checked</xsl:attribute>
<!--											<xsl:attribute name="disabled">disabled</xsl:attribute>-->
										</xsl:when>
										<xsl:when test="access_level = '7' or not(access_level) or access_level = ''">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:when>
									</xsl:choose>
								</input>
								Read and create threads <br/>
								<input type="radio" name="forum_access[{id}]" value="6">
									<xsl:choose>
										<xsl:when test="access_level = '6'">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:when>
									</xsl:choose>
								</input>									
								Read and reply to threads <br/>
								<input type="radio" name="forum_access[{id}]" value="4">
									<xsl:choose>
										<xsl:when test="access_level = '4'">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:when>
									</xsl:choose>
								</input>																		 
								Read only
							</small>
						</blockquote>
					</dd>
				</xsl:for-each>
			</dl>
			<input type="submit" value="save"/>
			&#160;
			<a href="forum_admin/deletegroup/{group/info/id}?session_id={php:function('session_id')}" onclick="if(!confirm('Sure?')) return false;">Delete group</a>
		</form>
	</xsl:template>	
	
	<xsl:template match="saveaccessgroup">	
	</xsl:template>	
	
	<xsl:template match="users">	
		<form method="get" action="forum"><input type="submit" value="&#171; back to forum"/></form>	
		<!-- preloading placeholder -->
		<xsl:if test="preload">
			<div id="preload" style="display: none"><xsl:value-of select="preload"/></div>
		</xsl:if>		
	
		<div class="cell-50" style="float: left; overflow: auto;">
			<h3>Users</h3>			
			<form method="get" action="forum_admin/users">
				<input type="text" name="s" value="{s}"/>&#160;<input type="submit" value="Search"/> 
			</form>
			<dl>
				<xsl:for-each select="users/users">
					<dd>
						<a href="javascript:;" onclick="Forms.fetch('/forum_profile/{user_id}','#forumadm-content',false);"><xsl:value-of select="username"/></a>
					</dd>
				</xsl:for-each>
				<dd>&#160;</dd>
			</dl>
			<div class="pagination">
				<xsl:call-template name="pagination">
					<xsl:with-param name="url"><xsl:value-of select="users/url"/></xsl:with-param>
					<xsl:with-param name="onlyButtons">1</xsl:with-param>					
					<xsl:with-param name="urlAppend"><xsl:if test="s!=''">?s=<xsl:value-of select="s"/></xsl:if></xsl:with-param>
					<xsl:with-param name="pageCurrent"><xsl:value-of select="users/pages/page"/></xsl:with-param>
					<xsl:with-param name="pageNext"><xsl:value-of select="users/pages/pageNext"/></xsl:with-param>
					<xsl:with-param name="pagePrev"><xsl:value-of select="users/pages/pagePrev"/></xsl:with-param>
					<xsl:with-param name="pageCount"><xsl:value-of select="users/pages/pages"/></xsl:with-param>
				</xsl:call-template>			
			</div>
		</div>
		<div class="cell-50" style="float: left; overflow: auto;" id="forumadm-content">
			(select user)
		</div>		
	</xsl:template>	
	
	<xsl:template match="forumactions">	
		<h3><xsl:value-of select="forum/name"/></h3>		
		<dl>
			<xsl:choose>
				<xsl:when test="forum/visible='1'">
					<dd><a href="forum_admin/hideforum/{forum/id}?session_id={php:function('session_id')}">Hide</a></dd>				
				</xsl:when>
				<xsl:otherwise>
					<dd><a href="forum_admin/showforum/{forum/id}?session_id={php:function('session_id')}">Make visible</a></dd>				
				</xsl:otherwise>
			</xsl:choose>
<!--			<dd><a href="forum_admin/deleteforum/{forum/id}" onclick="if(!confirm('Sure?')) return false;">Delete</a></dd>-->
		</dl>
	</xsl:template>	
	
	<xsl:template match="forums">	
		<form method="get" action="forum"><input type="submit" value="&#171; back to forum"/></form>	
		<form method="get" action="forum_admin/forumgroups"><input type="submit" value="Sort forums"/></form>	
		<h3>Forums</h3>
		<table>
			<tbody>
				<xsl:for-each select="groups">
					<tr>
						<td style="min-width: 350px"><a href="javascript:;" onclick="Forms.fetch('/forum_admin/groupactions/{id}','#admin-modal',true);"><xsl:value-of select="name"/></a>
							<br/>
							<ul>
								<xsl:for-each select="forums">
									<li><a href="javascript:;" onclick="Forms.fetch('/forum_admin/forumactions/{id}','#admin-modal',true);"><xsl:value-of select="name"/></a>&#160;<xsl:if test="visible='0'">(hidden)</xsl:if></li>		
								</xsl:for-each>	
								<li><a href="javascript:;" onclick="Forms.fetch('/forum_admin/addforumtogroup/{id}','#admin-modal',true);">Add new forum...</a></li>
							</ul>					
						</td>
						<td>&#160;</td>
					</tr>
				</xsl:for-each>
			</tbody>
		</table>	
		<br/>
		<a href="javascript:;" onclick="Forms.fetch('/forum_admin/addforumgroup','#admin-modal',true);">Add new forum group...</a>	
	</xsl:template>		
	
	<xsl:template match="addforumtogroup">	
		<form method="post" action="forum_admin/addforumtogroup/{group_id}?session_id={php:function('session_id')}">
			<h3>Add new forum</h3>
			<input type="text" name="forum_name" value="{forum_name}"/>
			<input type="submit" value="add"/>
		</form>
	</xsl:template>	
	
	<xsl:template match="addforumgroup">	
		<form method="post" action="forum_admin/addforumgroup?session_id={php:function('session_id')}">
			<h3>Add new forum group</h3>
			<input type="text" name="group_name" value="{group_name}"/>
			<input type="submit" value="add"/>
		</form>
	</xsl:template>		
	
	<xsl:template match="forumgroups">	
		<form method="get" action="forum_admin/forums"><input type="submit" value="&#171; back"/></form>	
		<h3>Forums</h3>
		
		<p>Drag to reorder</p>
		<form method="post" action="forum_admin/forumgroups">
			<table>
				<tbody class="sortable">
					<xsl:for-each select="groups">
						<tr>
							<td style="min-width: 350px" class="pointer-move"><input type="hidden" name="order[]" value="{id}"/><xsl:value-of select="name"/>
								<br/>
								<ul class="sortable">
									<xsl:for-each select="forums">
										<li><input type="hidden" name="forum-order[]" value="{id}"/><xsl:value-of select="name"/></li>		
									</xsl:for-each>	
								</ul>					
							</td>
							<td>&#160;</td>
						</tr>
					</xsl:for-each>
				</tbody>
			</table>
			<input type="submit" name="save" value="Save"/>
		</form>
	</xsl:template>		
	
	<xsl:template match="groupactions">	
		<h3><xsl:value-of select="group/group/name"/></h3>
		<h4>Group visibility</h4>
		<form method="post" action="forum_admin/savegroup/{group/group/id}?session_id={php:function('session_id')}">
			<input type="radio" name="public" value="1">
				<xsl:if test="group/group/public = '1'">
					<xsl:attribute name="checked">checked</xsl:attribute>
				</xsl:if>
<!--				<xsl:attribute name="onclick">
					$('.group-checkbox').attr('disabled','disabled');
				</xsl:attribute>-->
			</input>&#160;Public
			<br/>
			<input type="radio" name="public" value="0">
				<xsl:if test="group/group/public = '0'">
					<xsl:attribute name="checked">checked</xsl:attribute>
				</xsl:if>
<!--				<xsl:attribute name="onclick">
					$('.group-checkbox').removeAttr('disabled');
				</xsl:attribute>-->
			</input>&#160;Visible only for members of:
			<dl>
				<xsl:for-each select="group/groups">
					<dd>
						<input name="forum_group[]" value="{id}" type="checkbox" class="group-checkbox"> 
							<xsl:if test="access">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if><!--
							<xsl:if test="../../group/group/public = '1'">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>-->
						</input>
						&#160;
						<xsl:value-of select="name"/>
					</dd>
				</xsl:for-each>
			</dl>
			Default permissions:
			<dl>
				<dd>
					<input type="radio" name="forum_access" value="7">
						<xsl:choose>
							<xsl:when test="group/access_level = '7' or not(group/access_level) or group/access_level = ''">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:when>
						</xsl:choose>
					</input>
					Read and create threads
				</dd>
				<dd>
					<input type="radio" name="forum_access" value="6">
						<xsl:choose>
							<xsl:when test="group/access_level = '6'">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:when>
						</xsl:choose>
					</input>									
					Read and reply to threads <br/>
				</dd>
				<dd>
					<input type="radio" name="forum_access" value="4">
						<xsl:choose>
							<xsl:when test="group/access_level = '4'">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:when>
						</xsl:choose>
					</input>																		 
					Read only
				</dd>
			</dl>
			<input type="submit" value="Save"/>
		</form>
	</xsl:template>	
	
	<xsl:template match="savegroup">	
	</xsl:template>		

</xsl:stylesheet>
