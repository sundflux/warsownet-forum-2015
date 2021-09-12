<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<div id="wiki">
			<xsl:if test="document">
				<h1 class="document-title"><xsl:value-of select="document"/></h1>
			</xsl:if>
		
			<!-- modal dialogs -->
			<div id="modal" style="display: none"/>		
			<!-- wiki navigation -->
			<div id="f-path">
				<a href="{common/basehref}/wiki">Wiki index</a>			
				<xsl:if test="parent">
					<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
					<a href="/wiki/{parent}"><xsl:value-of select="parent"/></a> 
				</xsl:if>				
				<xsl:if test="/page/common/user">
					<xsl:choose>
						<xsl:when test="/page/common/user and locked = '1' and admin != '1'">
							<!-- regular user viewing locked document -->
						</xsl:when>
						<xsl:when test="/page/common/user and locked = '1' and admin = '1'">
							<!-- admin viewing a locked document -->
							<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
							<a href="javascript:;" onclick="Forms.fetch('{common/basehref}/wiki/{document}/edit', '#modal', true);">Edit page</a> 					
							<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
							<a href="javascript:;" onclick="Forms.fetch('{common/basehref}/wiki/{document}/upload', '#modal', true);">Upload attachment</a> 					
						</xsl:when>
						<xsl:otherwise>
							<!-- regular, unlocked documents -->
							<xsl:if test="not(notVerified)">
								<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
								<a href="javascript:;" onclick="Forms.fetch('{common/basehref}/wiki/{document}/edit', '#modal', true);">Edit page</a> 					
								<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
								<a href="javascript:;" onclick="Forms.fetch('{common/basehref}/wiki/{document}/upload', '#modal', true);">Upload attachment</a> 
							</xsl:if>
						</xsl:otherwise>
					</xsl:choose>
					<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
					<a href="{/page/common/binding}/{document}/history">View history</a> 					
					<a href="{/page/common/binding}/help" class="r">Wiki help and syntax</a>
				</xsl:if>				
				<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
				<xsl:if test="documentTOC != ''">
					<span id="toc-hide">
						<xsl:choose>
							<xsl:when test="show != '1'">
								<xsl:attribute name="style">display: none</xsl:attribute>								
							</xsl:when>
						</xsl:choose>
						<a href="javascript:;" onclick="$('#toc').hide();$('#toc-show').show();$('#toc-hide').hide();">Hide Table of Contents</a>
					</span>			
					<span id="toc-show">
						<xsl:choose>
							<xsl:when test="show != '0'">
								<xsl:attribute name="style">display: none</xsl:attribute>								
							</xsl:when>
						</xsl:choose>
						<a href="javascript:;" onclick="$('#toc').show();$('#toc-show').hide();$('#toc-hide').show();">Show Table of Contents</a>
					</span>
				</xsl:if>
			</div>			

			<xsl:if test="/page/common/user and locked = '1'">
				<div class="notification">
					This document is <b>locked</b> and can be only edited by administrators.
				</div>
			</xsl:if>

			<xsl:if test="/page/common/user and notVerified and locked != '1'">
				<div class="notification">
					Only verified accounts may edit this page.
				</div>
			</xsl:if>

			<!-- table of contents -->
			<xsl:if test="documentTOC != ''">
				<div id="toc">
					<a name="toc" class="hidden"/>
					<xsl:for-each select="documentTOC">
						<xsl:if test="link != ''">
								<a>
									<xsl:if test="class">
										<xsl:attribute name="class">
											<xsl:value-of select="class"/>
										</xsl:attribute>
									</xsl:if>
									<xsl:if test="pos">
										<xsl:choose>
											<xsl:when test="class='sub'">
												<xsl:attribute name="href">
													<xsl:value-of select="/page/common/binding"/>/<xsl:value-of select="../document"/>#<xsl:value-of select="h1"/>.<xsl:value-of select="pos"/>.
												</xsl:attribute>
												<xsl:value-of select="h1"/>.<xsl:value-of select="pos"/>
											</xsl:when>
											<xsl:when test="class='sub2'">
												<xsl:attribute name="href">
													<xsl:value-of select="/page/common/binding"/>/<xsl:value-of select="../document"/>#<xsl:value-of select="h1"/>.<xsl:value-of select="h2"/>.<xsl:value-of select="pos"/>.
												</xsl:attribute>
												<xsl:value-of select="h1"/>.<xsl:value-of select="h2"/>.<xsl:value-of select="pos"/>
											</xsl:when>
											<xsl:when test="class='sub3'">
												<xsl:attribute name="href">
													<xsl:value-of select="/page/common/binding"/>/<xsl:value-of select="../document"/>#<xsl:value-of select="h1"/>.<xsl:value-of select="h2"/>.<xsl:value-of select="h3"/>.<xsl:value-of select="pos"/>.
												</xsl:attribute>												
												<xsl:value-of select="h1"/>.<xsl:value-of select="h2"/>.<xsl:value-of select="h3"/>.<xsl:value-of select="pos"/>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="href">
													<xsl:value-of select="/page/common/binding"/>/<xsl:value-of select="../document"/>#<xsl:value-of select="pos"/>.
												</xsl:attribute>
												<xsl:value-of select="pos"/>.
											</xsl:otherwise>
										</xsl:choose>
										&#160;
									</xsl:if>
									<xsl:value-of select="link"/>
								</a>
								<br/>
						</xsl:if>
					</xsl:for-each>
				</div>
			</xsl:if>					
		
			<!-- wiki content start -->
			<xsl:value-of select="documentContent" disable-output-escaping="yes" />
		
			<!-- wiki content end -->
			<br class="c"/>
		
			<!-- attachments -->
			<xsl:if test="attachments">
				<hr class="nicehr"/>
				<div class="padded rounded alt3 attachments">
					<b>Attachments:</b>
					<br/>
					<xsl:for-each select="attachments">
						<a href="{filename}"><xsl:value-of select="filename_nopath"/></a>
						<!-- Can this user delete files? -->
						<xsl:if test="../admin or uploader_userid = ../user_id">
							&#160;<a href="{/page/common/binding}/{../document}/delete/{id}">(delete)</a>
						</xsl:if>
						<br/>
					</xsl:for-each>
				</div>
			</xsl:if>
			
			<!-- resize images -->	
			<script type="text/javascript">
				var size = $('#wiki').width() - ($('#wiki').width() / 60) ;
				$('#wiki * img').aeImageResize({ height: size, width: size }).show();
			</script>
			
		</div>
	</xsl:template>	
	
	<xsl:template match="edit">
		<h1>Edit <xsl:value-of select="document"/></h1>
		<form method="post" action="{/page/common/binding}/{document}/save">
			<textarea name="content" id="topic-content" class="topic-content" placeholder="What's on your mind?" style="width: 800px; height: 400px"><xsl:value-of select="documentContent/document_content"/></textarea>
			<br/>
			<input type="submit" name="save" value="Save"/>		
			<xsl:if test="admin = '1'">
				&#160;
				<input type="submit" name="save_lock" value="Save and lock"/>			
			</xsl:if>
		</form>
	</xsl:template>

	<xsl:template match="upload">
		<h1>Upload attachment</h1>
		<form method="post" action="{/page/common/binding}/{document}/upload" enctype="multipart/form-data">
			<label for="upload">Upload image:</label>
			<input type="file" name="upload" accept="image/*"/> 
			<br/>
			<small>(maximum size 2 MB. supported formats jpg, png and gif)</small>
			<br/>
			<br/>
			<input type="submit" name="submit" value="Upload"/>
			<br/>
		</form>
	</xsl:template>	

	<xsl:template match="history">
		<div id="wiki">
			<h1 class="document-title">History for <xsl:value-of select="document"/></h1>
			
			<!-- wiki navigation -->
			<div id="f-path">
				<a href="{common/basehref}/wiki">Wiki index</a>						
				<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
				<a href="{common/basehref}/wiki/{document}"><xsl:value-of select="document"/></a>						
				<a href="{/page/common/binding}/help" class="r">Wiki help and syntax</a>
			</div>	

			<form method="get" action="wiki/{document}/diff">
				<input type="submit" name="compare" value="Compare revisions"/>
				<div class="tbl">
					<div class="tbl-thead">
						<div class="tbl-row">
							<div class="tbl-cell rounded-left wiki-radio">&#160;</div>
							<div class="tbl-cell wiki-revision">Revision</div>
							<div class="tbl-cell wiki-document">Document</div>
							<div class="tbl-cell wiki-author">Author</div>
							<div class="tbl-cell wiki-created">Created</div>
							<xsl:if test="/page/common/user and not(notVerified)">
								<div class="tbl-cell wiki-actions rounded-right">Actions</div>
							</xsl:if>
						</div>
					</div>			
					<div class="tbl-tbody">
						<xsl:for-each select="history/history">
							<div class="tbl-row">
								<div class="tbl-cell rounded-left wiki-radio">
									<xsl:choose>
										<xsl:when test="position() = '1'">
											<input type="radio" name="revision-current" value="{revision}" class="l">
												<xsl:if test="position() = '1'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</xsl:when>
										<xsl:otherwise>
											<input type="radio" name="revision-prev" value="{revision}" class="r">
												<xsl:if test="position() = '2'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</xsl:otherwise>
									</xsl:choose>
								</div>
								<div class="tbl-cell wiki-revision"><xsl:value-of select="revision"/></div>
								<div class="tbl-cell wiki-document nowrap"><xsl:value-of select="document_title"/></div>
								<div class="tbl-cell wiki-author"><a href="forum_profile/{author_id}"><xsl:value-of select="author"/></a></div>
								<div class="tbl-cell wiki-created"><span class="datetime"><xsl:value-of select="created"/></span></div>
								<xsl:if test="/page/common/user and not(../../notVerified)">
									<div class="tbl-cell wiki-actions rounded-right"><a href="{/page/common/binding}/{../../document}/revert/{revision}">Revert to revision</a></div>
								</xsl:if>
							</div>
						</xsl:for-each>
					</div>
				</div>
			</form>

			<xsl:if test="history/pages/pages &gt; 1">
				<xsl:call-template name="pagination">
					<xsl:with-param name="url"><xsl:value-of select="history_url"/></xsl:with-param>
					<xsl:with-param name="pageCurrent"><xsl:value-of select="history/pages/page"/></xsl:with-param>
					<xsl:with-param name="pageNext"><xsl:value-of select="history/pages/pageNext"/></xsl:with-param>
					<xsl:with-param name="pagePrev"><xsl:value-of select="history/pages/pagePrev"/></xsl:with-param>
					<xsl:with-param name="pageCount"><xsl:value-of select="historypages/pages"/></xsl:with-param>
				</xsl:call-template>			
			</xsl:if>
		</div>
	</xsl:template>	

	<xsl:template match="diff">
		<div id="wiki">
			<h1 class="document-title">Revision&#160;<xsl:value-of select="compare_revision"/>&#160;vs&#160;<xsl:value-of select="previous_revision"/>&#160;for&#160;<xsl:value-of select="document"/></h1>

			<!-- wiki navigation -->
			<div id="f-path">
				<a href="{common/basehref}/wiki">Wiki index</a>						
				<div class="f-path-spacer"><img src="/site-images/fpath-spacer.png"/></div>
				<a href="{common/basehref}/wiki/{document}"><xsl:value-of select="document"/></a>						
				<a href="{/page/common/binding}/help" class="r">Wiki help and syntax</a>
			</div>			

			<b>Source diff:</b>
			<hr class="nicehr"/>
			<pre class="alt3 padded rounded">
				<xsl:value-of select="diffRaw" disable-output-escaping="yes" />
			</pre>
<!--
			<b>Wikicode parsed diff:</b>
			<hr class="nicehr"/>
			<div class="alt3 padded rounded">
				<xsl:value-of select="diff" disable-output-escaping="yes" />
			</div>-->
		</div>
	</xsl:template>	

	<xsl:template match="help">
		<div id="wiki">
			<h1 class="document-title">Wiki help and syntax</h1>
			<!-- wiki navigation -->
			<div id="f-path">
				<a href="{common/basehref}/wiki">Wiki index</a>						
				<a href="{/page/common/binding}/help" class="r">Wiki help and syntax</a>
			</div>		
		</div>

		<br/>
		<h1>Available tags</h1>

		<div class="tbl">
			<div class="tbl-tbody">
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">= text =</div>
					<div class="tbl-cell rounded-right wiki-help-right"><h1>text</h1></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">== text ==</div>
					<div class="tbl-cell rounded-right wiki-help-right"><h2>text</h2></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">=== text ===</div>
					<div class="tbl-cell rounded-right wiki-help-right"><h3>text</h3></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">==== text ====</div>
					<div class="tbl-cell rounded-right wiki-help-right"><h4>text</h4></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">''''' text '''''</div>
					<div class="tbl-cell rounded-right wiki-help-right"><b><i>text</i></b></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">''' text '''</div>
					<div class="tbl-cell rounded-right wiki-help-right"><b>text</b></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">'' text ''</div>
					<div class="tbl-cell rounded-right wiki-help-right"><i>text</i></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">----</div>
					<div class="tbl-cell rounded-right wiki-help-right"><hr class="nicehr"/></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left"><small>Attaching external images:</small><br/>[[img:http://www.foopics.com/showfull/96d7fc3d1783be416e1f1f3e5306fe0e|Alt tag]]</div>
					<div class="tbl-cell rounded-right wiki-help-right"><img src="http://www.foopics.com/showfull/96d7fc3d1783be416e1f1f3e5306fe0e" width="320" height="200"/></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left"><small>Attaching uploaded images:</small><br/>[[img:warsow.png|Alt tag]]</div>
					<div class="tbl-cell rounded-right wiki-help-right"><div class="wiki-image rounded padded"><img src="http://www.foopics.com/showfull/96d7fc3d1783be416e1f1f3e5306fe0e" width="320" height="200"/><br/><a href="javascript:;">warsow.png</a></div></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">[http://www.warsow.net/]</div>
					<div class="tbl-cell rounded-right wiki-help-right"><a href="[http://www.warsow.net/]">http://www.warsow.net/</a></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">[[WikiPage]]</div>
					<div class="tbl-cell rounded-right wiki-help-right"><a href="/wiki/WikiPage">WikiPage</a></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">[[WikiPage|Alternative title]]</div>
					<div class="tbl-cell rounded-right wiki-help-right"><a href="/wiki/WikiPage">Alternative title</a></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">[[ParentPage:WikiPage|Alternative title]]</div>
					<div class="tbl-cell rounded-right wiki-help-right"><a href="/wiki/ParentPage:WikiPage">Alternative title</a></div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left"># text<br/># text<br/># text<br/># text<br/></div>
					<div class="tbl-cell rounded-right wiki-help-right">
<ol>
<li>text</li>
<li>text</li>
<li>text</li>
<li>text</li>
</ol>
					</div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">* text<br/>* text<br/>* text<br/>* text<br/></div>
					<div class="tbl-cell rounded-right wiki-help-right">
<ul>
<li>text</li>
<li>text</li>
<li>text</li>
<li>text</li>
</ul>
					</div>
				</div>
				<div class="tbl-row">
					<div class="tbl-cell rounded-left wiki-help-left">[pre]preformatted text[/pre]
					</div>
					<div class="tbl-cell rounded-right wiki-help-right"><pre>preformatted text</pre></div>
				</div>
			</div>
		</div>
		+ any forum bbcode syntax.<br/>

	</xsl:template>

</xsl:stylesheet>
