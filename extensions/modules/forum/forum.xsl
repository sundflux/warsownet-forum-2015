<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<!-- main page, forums sorted by groups -->
	<xsl:template match="index">
	
		<!-- forum groups -->
		<xsl:for-each select="groups">

			<!-- group title, show edit form if admin -->
			<xsl:choose>
				<xsl:when test="../admin = '1'">
					<h1 id="group-title-{group_id}">
						<span class="pointer" onclick="$('#rename-form-{group_id}').show();$('#group-title-{group_id}').hide()" title="click to edit"><xsl:value-of select="group"/></span>
						<xsl:if test="public='1'">
							<span><a href="forum_rss/group/{group_id}"><img src="/forum-images/icons/set/rss_24x24.png" alt="RSS feed" width="24" height="24"/></a></span>
						</xsl:if>               
					</h1>                       
				</xsl:when>
				<xsl:otherwise>
					<h1>
						<xsl:value-of select="group"/>
						<xsl:if test="public='1'">
							<span><a href="forum_rss/group/{group_id}"><img src="/forum-images/icons/set/rss_24x24.png" alt="RSS feed" width="24" height="24"/></a></span>
						</xsl:if>           
					</h1>           
				</xsl:otherwise>
			</xsl:choose>       

			<!-- rename form -->
			<xsl:if test="../admin = '1'">
				<form method="post" action="forum_admin/renamegroup/{group_id}?session_id={php:function('session_id')}" class="large-form" id="rename-form-{group_id}" style="display: none">
					<input type="text" name="group_name" value="{group}"/>&#160;
					<input type="submit" name="save" value="Save" />&#160;
					<input type="button" value="Cancel" onclick="$('#rename-form-{group_id}').hide();$('#group-title-{group_id}').show()"/>
				</form>
			</xsl:if>
			
			<div class="tbl">
				<div class="tbl-thead">
					<div class="tbl-row">
						<div class="topic topic-f rounded-left">Forum</div>
						<div class="posts rounded-right">Topics</div>
						<div class="lastpost">Last post</div>
					</div>
				</div>
				<div class="tbl-tbody">
					<xsl:for-each select="forums">
						<xsl:variable name="user"><xsl:value-of select="last_post_by"/></xsl:variable>
						<div class="tbl-row">
							<div class="tbl-cell topic">
								<xsl:if test="../../admin = '1' or ../../moderator = '1'">
									<a onclick="Admin.showModal('-1','{id}','-1','-1');" href="javascript:;"><img src="/forum-images/icons/set/wrench_16x16.png" alt=""/></a>
									&#160;
								</xsl:if>                           
								<a href="{/page/common/binding}/{id}"><xsl:value-of select="name"/></a>
							</div>
							<div class="tbl-cell posts"><xsl:value-of select="topics"/></div>
							<div class="tbl-cell lastpost">
								<xsl:if test="last_post_id != '' and last_post_id != '0'">
									<a href="forum/thread/t/{last_post_id}#post-{last_post_id}">
										<span class="datetime"><xsl:value-of select="last_post"/></span></a> 
										by 
										<xsl:choose>
											<xsl:when test="../../users[user_id=$user]/alias != ''">
												<xsl:value-of select="../../users[user_id=$user]/alias"/>
											</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="../../users[user_id=$user]/username"/>
											</xsl:otherwise>
										</xsl:choose>
								</xsl:if>
							</div>
						</div>
					</xsl:for-each>
				</div>
			</div>
		</xsl:for-each>
	</xsl:template>
	
	<!-- display forum threads -->
	<xsl:template match="forum">    
		<!-- thread title, show edit form if admin -->
		<xsl:choose>
			<xsl:when test="admin = '1'">
				<h1 id="forum-title">               
					<xsl:if test="edit">
						<xsl:attribute name="style">display: none</xsl:attribute>
					</xsl:if>
					<!-- back button -->    
					<a class="fbackb" href="{common/basehref}forum">
						<img src="/forum-images/icons/set/arrow_left_24x24.png" alt="back" width="24" height="24"/>
					</a>
					
					<span onclick="$('#rename-form').show();$('#forum-title').hide()" title="click to edit"><xsl:value-of select="forum/name"/></span>
					<xsl:if test="forum/public='1'">
						<span><a href="forum_rss/forum/{forum/id}"><img src="/forum-images/icons/set/rss_24x24.png" alt="RSS feed" width="24" height="24"/></a></span>
					</xsl:if>                   
				</h1>                       
			</xsl:when>
			<xsl:otherwise>
				<h1>
					<!-- back button -->    
					<a class="fbackb" href="{common/basehref}forum">
						<img src="/forum-images/icons/set/arrow_left_24x24.png" alt="back" width="24" height="24"/>
					</a>
					<xsl:value-of select="forum/name"/>
					<xsl:if test="forum/public='1'">
						<span><a href="forum_rss/forum/{forum/id}"><img src="/forum-images/icons/set/rss_24x24.png" alt="RSS feed" width="24" height="24"/></a></span>
					</xsl:if>                   
				</h1>           
			</xsl:otherwise>
		</xsl:choose>
		
		<!-- rename form -->
		<xsl:if test="admin = '1'">
			<form method="post" action="forum_admin/renameforum/{forum_id}?session_id={php:function('session_id')}" class="large-form" id="rename-form" style="display: none">
				<xsl:if test="edit">
					<xsl:attribute name="style">display: block</xsl:attribute>
				</xsl:if>
				<input type="text" name="forum_name" value="{forum/name}"/>&#160;
				<input type="submit" name="save" value="Save" />&#160;
				<input type="button" value="Cancel" onclick="$('#rename-form').hide();$('#forum-title').show()"/>
			</form>
		</xsl:if>

		<div id="f-path">
			<a href="{common/basehref}/forum">Forum index</a>           
			<xsl:if test="/page/module/*/forum/name"><div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div><a href="{common/basehref}forum/{/page/module/*/forum/id}"><xsl:value-of select="/page/module/*/forum/name"/></a></xsl:if>          
			<xsl:if test="/page/module/*/forum/title"><div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div><xsl:value-of select="/page/module/*/forum/title"/></xsl:if>
		</div>
		
		<div class="tbl">
			<div class="tbl-thead">
				<div class="tbl-row">
					<div class="topic rounded-left">Topic</div>
					<div class="views rounded-right">Views</div>
					<div class="posts">Posts</div>
					<div class="lastpost">Last post</div>
				</div>
			</div>
			<div class="tbl-tbody">
				<xsl:for-each select="forum/threads">
					<div class="tbl-row">
						<div class="tbl-cell topic nowrap">
							<xsl:if test="../../admin = '1' or ../../moderator = '1'">
								<a onclick="Admin.showModal('{id}','{../forum_id}','-1','{../../forum/pages/page}');" href="javascript:;"><img src="/forum-images/icons/set/wrench_16x16.png" alt=""/></a>
								&#160;
							</xsl:if>
							<xsl:if test="pinned='1'">
								<img src="/forum-images/icons/set/heart_fill_16x14.png" alt="pinned"/>
								&#160;
							</xsl:if>
							<xsl:if test="closed='1'">
								<img src="/forum-images/icons/set/lock_stroke_12x16.png" alt="closed"/>
								&#160;
							</xsl:if>
							<a href="{/page/common/binding}/thread/{id}/1">
								<xsl:attribute name="title">
									<xsl:value-of select="title"/>
								</xsl:attribute>
								<xsl:value-of select="title"/>
							</a>
						</div>
						<div class="tbl-cell views"><xsl:value-of select="views"/></div>
						<div class="tbl-cell posts"><xsl:value-of select="posts"/></div>
						<div class="tbl-cell lastpost"><a href="forum/thread/t/{last_post_id}#post-{last_post_id}"><span class="datetime"><xsl:value-of select="last_post"/></span></a>&#160;by&#160;<xsl:value-of select="lastPostByName"/></div>
					</div>
				</xsl:for-each>
			</div>
		</div>
		<hr/>
		<div class="pagination">
			<a name="bottom" id="bottom"/>
			
			<!-- post button -->
			<xsl:if test="forum/permissions != '4' and forum/permissions != '6'">
				<div id="post-form">
					<a href="forum_topic/{forum/id}" id="new-topic"><img src="/forum-images/icons/set/plus_16x16.png" alt="" width="16" height="16"/> Post new topic</a>
				</div>
			</xsl:if>
			<!-- cancel button -->
			<div id="cancel-button" style="display: none;">
				<a href="javascript:;" onclick="Post.cancelAction();">Cancel</a>
			</div>
			<xsl:call-template name="pagination">
				<xsl:with-param name="url"><xsl:value-of select="forum/url"/></xsl:with-param>
				<xsl:with-param name="pageCurrent"><xsl:value-of select="forum/pages/page"/></xsl:with-param>
				<xsl:with-param name="pageNext"><xsl:value-of select="forum/pages/pageNext"/></xsl:with-param>
				<xsl:with-param name="pagePrev"><xsl:value-of select="forum/pages/pagePrev"/></xsl:with-param>
				<xsl:with-param name="pageCount"><xsl:value-of select="forum/pages/pages"/></xsl:with-param>
			</xsl:call-template>
		</div>
		<div id="loader" style="display: none;">
			<div id="floatingCirclesG">
				<div class="f_circleG" id="frotateG_01">
				</div>
				<div class="f_circleG" id="frotateG_02">
				</div>
				<div class="f_circleG" id="frotateG_03">
				</div>
				<div class="f_circleG" id="frotateG_04">
				</div>
				<div class="f_circleG" id="frotateG_05">
				</div>
				<div class="f_circleG" id="frotateG_06">
				</div>
				<div class="f_circleG" id="frotateG_07">
				</div>
				<div class="f_circleG" id="frotateG_08">
				</div>
			</div>          
		</div>
		<div id="post" style="display: none;"/>
	</xsl:template> 
	
	<!-- thread -->
	<xsl:template match="thread">
		<a name="top" id="top"/>    
		
		<!-- thread title, show edit form if admin -->
		<xsl:choose>
			<xsl:when test="admin = '1' or moderator = '1' or isowner = '1'">
				<h1 id="thread-title" class="nowrap">
					<xsl:if test="edit">
						<xsl:attribute name="style">display: none</xsl:attribute>
					</xsl:if>           
					<!-- back button -->    
					<xsl:if test="/page/module/*/forum/name">
						<a class="fbackb" href="{common/basehref}forum/{/page/module/*/forum/id}/{backToPage}">
							<img src="/forum-images/icons/set/arrow_left_24x24.png" alt="back" width="24" height="24"/>
						</a>
					</xsl:if>                   
					<span onclick="$('#rename-form').show();$('#thread-title').hide()" title="click to edit"><xsl:value-of select="forum/title"/></span>
					
					<!-- bookmark -->
					<xsl:if test="/page/common/user">
						<span id="thread-bookmark">
							<xsl:choose>
								<xsl:when test="isBookmarked">
									<xsl:attribute name="onclick">Bookmark.deleteBookmark(<xsl:value-of select="forum/thread_id"/>, '<xsl:value-of select="php:function('session_id')"/>')</xsl:attribute>
									<img id="bookmark-img" src="/forum-images/icons/set/book_active.png" alt="Click to remove bookmark" width="24" height="24"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="onclick">Bookmark.addBookmark(<xsl:value-of select="forum/thread_id"/>,  '<xsl:value-of select="php:function('session_id')"/>')</xsl:attribute>
									<img id="bookmark-img" src="/forum-images/icons/set/book_inactive.png" alt="Click to bookmark" width="24" height="24"/>                         
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</xsl:if>
					
				</h1>                       
			</xsl:when>
			<xsl:otherwise>
				<h1>
					<!-- back button -->
					<xsl:if test="/page/module/*/forum/name">
						<a class="fbackb" href="{common/basehref}forum/{/page/module/*/forum/id}/{backToPage}">
							<img src="/forum-images/icons/set/arrow_left_24x24.png" alt="back" width="24" height="24"/>
						</a>
					</xsl:if>
					<span class="nowrap"><xsl:value-of select="forum/title"/></span>
					
					<!-- bookmark -->
					<xsl:if test="/page/common/user">
						<span id="thread-bookmark">
							<xsl:choose>
								<xsl:when test="isBookmarked">
									<xsl:attribute name="onclick">Bookmark.deleteBookmark(<xsl:value-of select="forum/thread_id"/>, '<xsl:value-of select="php:function('session_id')"/>')</xsl:attribute>
									<img id="bookmark-img" src="/forum-images/icons/set/book_active.png" alt="Click to remove bookmark" width="24" height="24"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="onclick">Bookmark.addBookmark(<xsl:value-of select="forum/thread_id"/>, '<xsl:value-of select="php:function('session_id')"/>')</xsl:attribute>
									<img id="bookmark-img" src="/forum-images/icons/set/book_inactive.png" alt="Click to bookmark" width="24" height="24"/>                         
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</xsl:if>
					
				</h1>           
			</xsl:otherwise>
		</xsl:choose>
		
		<!-- rename form -->
		<xsl:if test="admin = '1' or moderator = '1' or isowner = '1'">
			<form method="post" action="forum/renameThread/{forum/thread_id}?session_id={php:function('session_id')}" class="large-form" id="rename-form" style="display: none">
				<xsl:if test="edit">
					<xsl:attribute name="style">display: block</xsl:attribute>
				</xsl:if>                           
				<input type="text" name="topic_name" value="{forum/title}"/>&#160;
				<input type="submit" name="save" value="Save" />&#160;
				<input type="button" value="Cancel" onclick="$('#rename-form').hide();$('#thread-title').show()"/>
			</form>
		</xsl:if>
		
		<div id="f-path" class="nowrap">
			<a href="{common/basehref}/forum">Forum index</a>           
			<xsl:if test="/page/module/*/forum/name"><div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div><a href="{common/basehref}forum/{/page/module/*/forum/id}"><xsl:value-of select="/page/module/*/forum/name"/></a></xsl:if>          
			<xsl:if test="/page/module/*/forum/title"><div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div><xsl:value-of select="/page/module/*/forum/title"/></xsl:if>
		</div>      
		
		<div class="tbl">
			<div class="tbl-thead">
				<div class="tbl-row">
					<div class="tbl-cell user rounded-left">Posted by</div>
					<div class="tbl-cell content rounded-right">
						Post                        
						<span class="scroller"><a href="{pageuri}#bottom" onclick="Post.scrollTo('#bottom');"><img src="/forum-images/icons/set/arrow_down_12x12.png" alt="Scroll to bottom"/></a></span>
						<div class="pagination" style="float: right">
							<xsl:call-template name="pagination">
								<xsl:with-param name="url"><xsl:value-of select="forum/url"/></xsl:with-param>
								<xsl:with-param name="pageCurrent"><xsl:value-of select="forum/pages/page"/></xsl:with-param>
								<xsl:with-param name="pageNext"><xsl:value-of select="forum/pages/pageNext"/></xsl:with-param>
								<xsl:with-param name="pagePrev"><xsl:value-of select="forum/pages/pagePrev"/></xsl:with-param>
								<xsl:with-param name="pageCount"><xsl:value-of select="forum/pages/pages"/></xsl:with-param>
							</xsl:call-template>                        
						</div>                      
					</div>
				</div>
			</div>
			<div class="tbl-tbody">
				<xsl:for-each select="forum/posts">
					<xsl:variable name="user"><xsl:value-of select="user_id"/></xsl:variable>
					<div class="tbl-row">
						<div class="tbl-cell user">
							<!-- anchor to post -->
							<a name="post-{post_id}"/>
							<!-- poster info -->
														
							<a href="{/page/common/binding}_profile/{user_id}">
							<xsl:choose>
								<xsl:when test="../users[user_id=$user]/alias != ''">
									<xsl:value-of select="../users[user_id=$user]/alias"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="../users[user_id=$user]/username"/>
								</xsl:otherwise>
							</xsl:choose>
							</a>
							<xsl:if test="../users[user_id=$user]/banned='1'">
								<br/>
								<small><b class="banned">Banned</b></small>
							</xsl:if>
							<br/>
														
							<xsl:choose>
															
								<!-- external avatar -->
								<xsl:when test="../users[user_id=$user]/external_avatar!=''">
									<img src="{../users[user_id=$user]/external_avatar}" alt=""/>
									<br/>
								</xsl:when>

								<!-- local avatar of gravatar -->
								<xsl:when test="../users[user_id=$user]/avatar!=''">
									<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='0'">
										<img src="{../../avatarurl}/{../users[user_id=$user]/avatar}" alt=""/>
									</xsl:if>
									<xsl:if test="../users[user_id=$user]/avatar!='' and ../users[user_id=$user]/gravatar='1'">
										<img src="http://www.gravatar.com/avatar/{../users[user_id=$user]/avatar}?s=60&amp;d=wavatar&amp;r=PG" alt=""/>
									</xsl:if>
									<br/>
								</xsl:when>
								
							</xsl:choose>
														
							<small>
								<xsl:if test="../users[user_id=$user]/alias != ''">
									<img src="/site-images/ext/steam.gif" alt="" class="ext-steam"/>&#160;
								</xsl:if>
								<xsl:choose>
									<xsl:when test="not(../users[user_id=$user]/title) or ../users[user_id=$user]/title=''">
										<xsl:value-of select="../../default_title"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="../users[user_id=$user]/title"/>                                  
									</xsl:otherwise>
								</xsl:choose>
								<br/>
								(<xsl:value-of select="../users[user_id=$user]/posts"/>&#160;posts)
							</small>
							<br/>
							<xsl:if test="../users[user_id=$user]/user_from!=''">
								<small>From: <xsl:value-of select="../users[user_id=$user]/user_from"/></small>
							</xsl:if>                       
						</div>
						<div class="tbl-cell content heighted">
							<!-- toolbox -->
							<div class="toolbox">
								<span class="date">
									<a href="forum/thread/t/{post_id}#post-{post_id}"><span class="datetime"><xsl:value-of select="created"/></span></a>
								</span>
								
								<xsl:if test="../forum/permissions = '6' or ../forum/permissions = '' or not(../forum/permissions)">
									<xsl:if test="not(../forum/closed) or ../forum/closed != '1'">                              
										<span class="quote-reply"><a href="forum_topic/{../../forum/id}/{../../forum/thread_id}/{post_id}" class="getquoted" id="quote-{post_id}"><img src="/forum-images/icons/sanscons/comment.png" alt="quote and reply"/>quote and reply</a></span>
									</xsl:if>
								</xsl:if>
								
								<xsl:if test="/page/common/userid = user_id or ../../admin = '1' or ../../moderator = '1'">
									<span class="edit-post"><a href="forum_topic/{../../forum/id}/{../../forum/thread_id}/{post_id}/edit" class="getquoted-clean" id="edit-{post_id}"><img src="/forum-images/icons/sanscons/edit.png" alt="edit post"/>edit post</a></span>                                
								</xsl:if>
								<xsl:if test="../../admin = '1' or ../../moderator = '1'">
									<span class="edit-post"><a onclick="if(!confirm('Sure?')) return false;" href="forum_admin/postAsSpam/{post_id}/{../../forum/thread_id}/{../../forum/pages/page}?session_id={php:function('session_id')}">spam</a></span>                               
									<span class="edit-post"><a onclick="if(!confirm('Sure?')) return false;" href="forum_admin/postAsSpamWithBan/{post_id}/{../../forum/thread_id}/{../../forum/pages/page}?session_id={php:function('session_id')}">spam + ban</a></span>                              
								</xsl:if>
								
								<br class="clear"/>
							</div>
							<div id="post-content-{post_id}">
								<xsl:value-of select="content" disable-output-escaping="yes"/>
								<br/>
								<!-- updated at.. -->
								<xsl:if test="updated!=''">
									<i class="updated">(updated <span class="datetime"><xsl:value-of select="updated"/></span>)</i>
								</xsl:if>
							</div>
							<br class="clear"/>
							<!-- signature -->
							<xsl:if test="../users[user_id=$user]/signatureView!=''">
								<div class="signature">
									<xsl:value-of select="../users[user_id=$user]/signatureView" disable-output-escaping="yes"/>
								</div>
							</xsl:if>
						</div>
					</div>
				</xsl:for-each>
			</div>
		</div>
		<div id="image-max-width" style="display: none;"/>
		<hr class="c"/>
		<span class="scroller clear"><a href="{pageuri}#top" onclick="Post.scrollTo('#top');"><img src="/forum-images/icons/set/arrow_up_12x12.png" alt="Scroll to top"/></a></span>
		<a name="bottom" id="bottom"/>
		<div class="pagination">
			<!-- post button -->
			<xsl:if test="forum/permissions = '6' or forum/permissions = '7' or forum/permissions = '' or not(forum/permissions)">
				<xsl:if test="not(forum/closed) or forum/closed != '1'">
					<div id="post-form">
						<a href="forum_topic/{forum/id}/{forum/thread_id}" id="new-topic"><img src="/forum-images/icons/set/plus_16x16.png" alt="" width="16" height="16"/> Reply to topic</a>
					</div>      
					<!-- cancel button -->
					<div id="cancel-button" style="display: none;">
						<a href="javascript:;" onclick="Post.cancelAction();">Cancel</a>
					</div>          
				</xsl:if>
			</xsl:if>
			<xsl:call-template name="pagination">
				<xsl:with-param name="url"><xsl:value-of select="forum/url"/></xsl:with-param>
				<xsl:with-param name="pageCurrent"><xsl:value-of select="forum/pages/page"/></xsl:with-param>
				<xsl:with-param name="pageNext"><xsl:value-of select="forum/pages/pageNext"/></xsl:with-param>
				<xsl:with-param name="pagePrev"><xsl:value-of select="forum/pages/pagePrev"/></xsl:with-param>
				<xsl:with-param name="pageCount"><xsl:value-of select="forum/pages/pages"/></xsl:with-param>
			</xsl:call-template>
		</div>
		<div id="loader" style="display: none;">
			<div id="floatingCirclesG">
				<div class="f_circleG" id="frotateG_01">
				</div>
				<div class="f_circleG" id="frotateG_02">
				</div>
				<div class="f_circleG" id="frotateG_03">
				</div>
				<div class="f_circleG" id="frotateG_04">
				</div>
				<div class="f_circleG" id="frotateG_05">
				</div>
				<div class="f_circleG" id="frotateG_06">
				</div>
				<div class="f_circleG" id="frotateG_07">
				</div>
				<div class="f_circleG" id="frotateG_08">
				</div>
			</div>      
		</div>
		<div id="post" style="display: none;"/>         
	</xsl:template> 

</xsl:stylesheet>
