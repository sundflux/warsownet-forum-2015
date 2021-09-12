<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<xsl:template match="index">
		<!-- search box (fetched via ajax -->
		<div id="search-area">
			<table>
				<tr>
					<th class="loader"><img src="{/page/common/basehref}/forum-images/ajax-loader.gif" alt="Searching..." style="display: none;" id="search-loader"/></th>
					<th>
						<xsl:if test="forum_id"><div id="forum_id" style="display:none;"><xsl:value-of select="forum_id"/></div></xsl:if>
						<input type="text" id="search-field" value="{search_field}" placeholder="What you're looking for?"/> 
						<input type="button" id="search-btn" value="search" onclick="Search.search();"/>
						<small>Search from <xsl:value-of select="searchScope"/> or try <a href="forum_search/advanced">extended search</a> or <a href="javascript:;" onclick="$('#search-box').slideUp('fast');">close search.</a></small>
					</th>
				</tr>
			</table>
		</div>
		<!-- trigger enter -->
		<script type="text/javascript">
			$('#search-field').keyup(function(e) {
				if(e.keyCode == 13) {
					Search.search();
				}
			});		
		</script>
		<div id="search-result"/>
	</xsl:template>
		
	<xsl:template match="doSearch">
		<!-- ajax search results -->
		<div class="search-results">
			<h1>Search results</h1>
			<hr/>
			<xsl:choose>
				<xsl:when test="wait">
					<xsl:value-of select="wait"/>
				</xsl:when>
				<xsl:when test="not(searchResult/result) and not(wait)">
					<p>No results</p>
				</xsl:when>
				<xsl:otherwise>
					<div class="tbl">
						<div class="tbl-thead">
							<div class="tbl-row">
								<div class="tbl-cell topic search-topic rounded-left">Topic</div>
								<div class="tbl-cell lastpost rounded-right">In forum</div>
								<div class="tbl-cell lastpost">Posted by</div>
							</div>
						</div>
						<div class="tbl-tbody">
							<xsl:for-each select="searchResult/result">
								<div class="tbl-row" title="{post_content}">
									<div class="tbl-cell topic nowrap"><a href="forum/thread/{topic_id}/1"><xsl:value-of select="topic_title"/></a></div>
									<div class="tbl-cell lastpost"><a href="forum/{forum_id}"><xsl:value-of select="forum_name"/></a></div>
									<div class="tbl-cell lastpost"><span class="datetime"><xsl:value-of select="post_created"/></span> by <xsl:value-of select="username"/></div>
								</div>
							</xsl:for-each>
						</div>
					</div>
					<span class="scroller"><a href="forum_search/advanced?query={query}&amp;forum_id={forum_id}">Extended search</a></span>
					<script type="text/javascript">
						Table.Colorize();
						Dates.format();
					</script>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>	
	
	<xsl:template match="advanced">
		<!-- advanced/non-js search -->
		<h1>Search forums</h1>
		<form method="get" action="forum_search/advanced">
			Search for <input type="text" value="{query}" name="query" placeholder="What you're looking for?"/>
			in forum
			<!-- dropdown to select target forum -->
			<select name="forum_id" id="forum-select-list">
				<xsl:if test="multiforum">
					<xsl:attribute name="disabled">disabled</xsl:attribute>
				</xsl:if>			
				<option value="">
					<xsl:if test="not(../../forum_id) or ../../forum_id=''">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					All forums
				</option>
				<xsl:for-each select="groups">
					<optgroup label="{group}">
						<xsl:for-each select="forums">
							<option value="{id}">
								<xsl:if test="../../forum_id=id">
									<xsl:attribute name="selected">selected</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="name"/>
							</option>
						</xsl:for-each>
					</optgroup>
				</xsl:for-each>
			</select>
			&#160;
			<input type="submit" name="submit" value="search"/>
			<!-- select multiple forums for searching -->
			<p id="forums-advanced">
				<xsl:if test="multiforum">
					<xsl:attribute name="style">display: block;</xsl:attribute>
				</xsl:if>
				or <a href="javascript:;" onclick="$('#forums-advanced-list').slideToggle('fast');Search.toggleMode();">select forums.</a>
			</p>
			<div id="forums-advanced-list">
				<xsl:if test="multiforum">
					<xsl:attribute name="style">display: block;</xsl:attribute>
				</xsl:if>
				<xsl:for-each select="groups">
					<h3><xsl:value-of select="group"/></h3>
					<xsl:for-each select="forums">
						<input type="checkbox" name="forums[{id}]">
							<xsl:if test="../../selected/val=id">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
						</input>
						&#160;<xsl:value-of select="name"/>
						<br/>
					</xsl:for-each>
				</xsl:for-each>
			</div>
		</form>
		<hr/>
		<!-- results -->
		<div id="search-results">
			<xsl:if test="searchResult/result"><h1>Search results<span><a href="{searchResult/url}{page}&amp;rss=1"><img src="{/page/common/basehref}/forum-images/feed-icon-28x28.png" alt="RSS feed" width="28" height="28"/></a></span></h1></xsl:if>
			<xsl:choose>
				<xsl:when test="wait">
					<xsl:value-of select="wait"/>
				</xsl:when>
				<xsl:when test="not(searchResult/result) and not(wait)">
					<p>No results</p>
				</xsl:when>
				<xsl:otherwise>
					<div class="tbl">
						<div class="tbl-thead">
							<div class="tbl-row">
								<div class="tbl-cell topic search-topic rounded-left">Topic</div>
								<div class="tbl-cell lastpost rounded-right">In forum</div>
								<div class="tbl-cell lastpost">Posted by</div>
							</div>
						</div>
						<div class="tbl-tbody">
							<xsl:for-each select="searchResult/result">
								<div class="tbl-row" title="{post_content}">
									<div class="tbl-cell topic nowrap"><a href="forum/thread/{topic_id}/1"><xsl:value-of select="topic_title"/></a></div>
									<div class="tbl-cell lastpost"><a href="forum/{forum_id}"><xsl:value-of select="forum_name"/></a></div>
									<div class="tbl-cell lastpost"><span class="datetime"><xsl:value-of select="post_created"/></span> by <xsl:value-of select="username"/></div>
								</div>
							</xsl:for-each>
						</div>
					</div>

					<hr/>
					<div class="pagination">
						<xsl:call-template name="pagination">
							<xsl:with-param name="url"><xsl:value-of select="searchResult/url"/></xsl:with-param>
							<xsl:with-param name="pageCurrent"><xsl:value-of select="searchResult/pages/page"/></xsl:with-param>
							<xsl:with-param name="pageNext"><xsl:value-of select="searchResult/pages/pageNext"/></xsl:with-param>
							<xsl:with-param name="pagePrev"><xsl:value-of select="searchResult/pages/pagePrev"/></xsl:with-param>
							<xsl:with-param name="pageCount"><xsl:value-of select="searchResult/pages/pages"/></xsl:with-param>
						</xsl:call-template>
					</div>			
										
					<script type="text/javascript">
						Table.Colorize();
						Dates.format();
					</script>
				</xsl:otherwise>
			</xsl:choose>
		</div>		
	</xsl:template>			

</xsl:stylesheet>
