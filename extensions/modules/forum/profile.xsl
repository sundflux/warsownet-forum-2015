<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<h1>
                    <xsl:choose>
                        <xsl:when test="profile/alias != ''">
                            <xsl:value-of select="profile/alias"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="profile/username"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </h1>
		<xsl:if test="profile/posts = '0'">		
			This user has not posted anything yet.
			<br/>
		</xsl:if>
		<xsl:if test="profile/banned = '1'">		
			This user is banned.
			<br/>			
			<br/>
			
			<xsl:if test="admin = '1' or moderator = '1'">
				<xsl:if test="/page/common/userid != profile/user_id">
					<xsl:choose>
						<xsl:when test="profile/banned = '1'">
							<a href="forum_admin/removeBan/{profile/id}/{profile/user_id}?session_id={php:function('session_id')}">Unban user</a>							
						</xsl:when>
						<xsl:otherwise>
							<a href="forum_admin/setBan/{profile/id}/{profile/user_id}?session_id={php:function('session_id')}">Ban user</a>							
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
			</xsl:if>			
		</xsl:if>
		
		<xsl:if test="profile/posts &gt; '0' and profile/banned != '1' ">		
			<xsl:if test="profile/avatar!='' and profile/gravatar='0'">
				<img src="{avatarurl}/{profile/avatar}" alt="avatar"/>
			</xsl:if>
			<xsl:if test="profile/avatar!='' and profile/gravatar='1'">
				<img src="http://www.gravatar.com/avatar/{profile/avatar}?s=60&amp;d=wavatar&amp;r=PG" alt=""/>
			</xsl:if>
			<br/>
			<div class="tbl">
				<div class="tbl-tbody">
					<xsl:if test="profile/real_name!=' '">
						<div class="tbl-row white">
							<div class="tbl-cell b">Name:</div>
							<div class="tbl-cell"><xsl:value-of select="profile/real_name"/> &#160;</div>
						</div>
					</xsl:if>
					<xsl:if test="profile/user_from!=''">
						<div class="tbl-row white">
							<div class="tbl-cell b">From:</div>
							<div class="tbl-cell"><xsl:value-of select="profile/user_from"/> &#160;</div>
						</div>
					</xsl:if>
					<div class="tbl-row white">
						<div class="tbl-cell b">Posts:</div>
						<div class="tbl-cell"><xsl:value-of select="profile/posts"/> &#160;</div>
					</div>
					<xsl:if test="not(customProfile)">
						<div class="tbl-row white">
							<div class="tbl-cell b">Member since:</div>
							<div class="tbl-cell"><span class="datetime"><xsl:value-of select="profile/joined"/></span> &#160;</div>
						</div>
					</xsl:if>
					<div class="tbl-row white">
						<div class="tbl-cell b">Title</div>
						<div class="tbl-cell">
							<xsl:choose>
								<xsl:when test="profile/title=''">
									<xsl:value-of select="default_title"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="profile/title"/>							
								</xsl:otherwise>
							</xsl:choose>					
						</div>
					</div>
					<xsl:if test="profile/www!=''">
						<div class="tbl-row white">
							<div class="tbl-cell b">Website:</div>
							<div class="tbl-cell"><a href="{profile/www}"><xsl:value-of select="profile/www"/></a></div>
						</div>
					</xsl:if>
					<xsl:if test="showEdit">
						<div class="tbl-row white">
							<div class="tbl-cell b">&#160;</div>
							<div class="tbl-cell"><a href="{/page/common/binding}/edit/{user_id}?session_id={php:function('session_id')}" id="edit-profile">Edit profile</a></div>
						</div>		
						<xsl:if test="isOwner">
							<div class="tbl-row white">
								<div class="tbl-cell b">&#160;</div>
								<div class="tbl-cell"><a href="forum_passwd">Change password</a></div>
							</div>
						</xsl:if>
					</xsl:if>				
					<xsl:if test="admin = '1' or moderator = '1'">
						<xsl:if test="/page/common/userid != profile/user_id">
							<div class="tbl-row white">
								<div class="tbl-cell b">&#160;</div>
								<div class="tbl-cell">
									<xsl:choose>
										<xsl:when test="profile/banned = '1'">
											<a href="forum_admin/removeBan/{profile/id}/{profile/user_id}?session_id={php:function('session_id')}">Unban user</a>							
										</xsl:when>
										<xsl:otherwise>
											<a href="forum_admin/setBan/{profile/id}/{profile/user_id}?session_id={php:function('session_id')}">Ban user</a>							
										</xsl:otherwise>
									</xsl:choose>
								</div>
							</div>				
						</xsl:if>
					</xsl:if>
					<xsl:if test="/page/common/userid and /page/common/userid != profile/user_id">
						<div class="tbl-row white">
							<div class="tbl-cell b">&#160;</div>
							<div class="tbl-cell">
								<a href="forum_pm?d={profile/user_id}">Send private message</a>
							</div>
						</div>						
					</xsl:if>
				</div>		
			</div>			
			<br/>
			<xsl:if test="profile/signatureView">
				<hr class="nicehr"/>
				<div class="profile-signature">
					<xsl:value-of select="profile/signatureView" disable-output-escaping="yes"/>
					<br class="c"/>
				</div>
			</xsl:if>			
		</xsl:if> <!-- end -->
	</xsl:template>
	
	<xsl:template match="edit">
                <h1>
                    <xsl:choose>
                        <xsl:when test="profile/alias != ''">
                            <xsl:value-of select="profile/alias"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="profile/username"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </h1>
		<div class="profile">
			<div class="profile-cell">
				<xsl:if test="profile/avatar!='' and profile/gravatar='0'">
					<img src="{avatarurl}/{profile/avatar}" alt="avatar"/>
				</xsl:if>
				<xsl:if test="profile/avatar!='' and profile/gravatar='1'">
					<img src="http://www.gravatar.com/avatar/{profile/avatar}?s=60&amp;d=wavatar&amp;r=PG" alt=""/>
				</xsl:if>
			</div>
			<div class="profile-cell">
				<form method="post" action="forum_profile/save/{user_id}?session_id={php:function('session_id')}" enctype="multipart/form-data">
					<label for="real_name">Name</label>
					<input type="text" name="real_name" value="{profile/real_name}"/>
					
					<label for="title">Title</label>
					<input type="text" name="title" value="{profile/title}"/>
					
					<label for="user_from">From</label>
					<input type="text" name="user_from" value="{profile/location}"/>
					
					<label for="www">Website</label>
					<input type="text" name="www" value="{profile/www}"/>

					<label for="email">Email (required, not shown publicly)</label>
					<input type="text" name="email" value="{profile/email}"/>
					
					<label for="gravatar">Use <a href="http://www.gravatar.com" target="_blank">gravatar.com</a> avatar</label>
					<input type="checkbox" name="gravatar" onclick="$('#avatar').toggle();">
						<xsl:if test="profile/gravatar='1'">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
					
					<div id="avatar">
						<xsl:if test="profile/gravatar='1'">
							<xsl:attribute name="style">display: none;</xsl:attribute>
						</xsl:if>						
						<label for="avatar">Upload avatar:</label>
						<input type="file" name="avatar" accept="image/*"/> 
						<br/>
						<small>(maximum size 80x80, 15kb. supported formats jpg, png and gif)</small>
					</div>

					<br/>
					
					<label for="bio">Biography</label>
					<xsl:value-of select="profile/bioView" disable-output-escaping="yes"/>
					<br/>
					<textarea name="bio" class="profile-signature"><xsl:value-of select="profile/bio" disable-output-escaping="yes"/></textarea>
					
					<br/>

					<label for="signature">Signature</label>
					<xsl:value-of select="profile/signatureView" disable-output-escaping="yes"/>
					<br/>
					<textarea name="signature" class="profile-signature"><xsl:value-of select="profile/signature" disable-output-escaping="yes"/></textarea>
					
					<br/>
					
					<xsl:if test="admin = '1'">
						<label for="admin">Administrator rights <br/> <small>Administrators can create, edit and delete forums, topics, users.</small></label>
						<input type="checkbox" name="admin">
							<xsl:if test="profile/admin = '1'">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
						</input>
						<br/>

						<label for="moderator">Moderator rights <br/> <small>Moderator can edit, remove and sort threads, mark threads and posts as spam and ban users.</small></label>
						<input type="checkbox" name="moderator">
							<xsl:if test="profile/moderator = '1'">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
						</input>
						<br/>		
						
						<h3>Groups:</h3>
					
						<dl>
							<xsl:for-each select="accessgroups">
								<dd>
									<input name="accessgroup[]" value="{id}" type="checkbox" class="group-checkbox"> 
										<xsl:if test="access">
											<xsl:attribute name="checked">checked</xsl:attribute>
										</xsl:if>
									</input>
									&#160;
									<xsl:value-of select="name"/>
								</dd>
							</xsl:for-each>
						</dl>						
					</xsl:if>			

					<label for="submit">&#160;</label>
					<input type="submit" name="submit" value="Save"/>
				</form>
			</div>
		</div>
		<br class="clear"/>
	</xsl:template>	

</xsl:stylesheet>
