<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

	<!-- pagination main template. calls 'pages' template for numbers. -->	
	<xsl:template name="pagination">
		<xsl:param name="url"/>
		<xsl:param name="urlAppend"/>
		<xsl:param name="onlyButtons"/>
		<xsl:param name="pageCurrent"/>
		<xsl:param name="pageNext"/>
		<xsl:param name="pagePrev"/>
		<xsl:param name="pageCount"/>
		<xsl:choose>
			<xsl:when test="$pagePrev='' or not($pagePrev)">
				<span class="prev"><strong>&#171;</strong></span>
				<span class="prev"><strong>Previous</strong></span>
			</xsl:when>
			<xsl:otherwise>
				<a class="prev" href="{$url}1{$urlAppend}" title="Jump to first page"><strong>&#171;</strong></a>
				<a class="prev" href="{$url}{$pagePrev}{$urlAppend}"><strong>Previous</strong></a>
			</xsl:otherwise>	
		</xsl:choose>
		<xsl:if test="not($onlyButtons)">
			<xsl:call-template name="pages">
				<xsl:with-param name="max" select="$pageCount"/>
				<xsl:with-param name="url" select="$url"/>				
				<xsl:with-param name="urlAppend" select="$urlAppend"/>				
				<xsl:with-param name="current" select="$pageCurrent"/>				
			</xsl:call-template>
		</xsl:if>
		<xsl:choose>
			<xsl:when test="$pageNext='' or not($pageNext)">
				<span class="next"><strong>Next</strong></span>
				<span class="next"><strong>&#187;</strong></span>
			</xsl:when>
			<xsl:otherwise>
				<a class="next" href="{$url}{$pageNext}{$urlAppend}"><strong>Next</strong></a>
				<a class="next" href="{$url}{$pageCount}{$urlAppend}" title="Jump to last page"><strong>&#187;</strong></a>
			</xsl:otherwise>	
		</xsl:choose>
	</xsl:template>
	
	<!-- page number links for pagination -->
	<xsl:template name="pages">
		<xsl:param name="url"/>
		<xsl:param name="urlAppend"/>
		<xsl:param name="max" select="1"/>
		<xsl:param name="count" select="0"/>
		<xsl:param name="current" select="1"/>
		<xsl:if test="$count &lt; $max">
				<xsl:if test="$count &gt; $current - 7 and $count &lt; $current + 5">
				<a href="{$url}{$count+1}{urlAppend}">
					<!-- mark current page -->
					<xsl:if test="$count+1 = $current">
						<xsl:attribute name="class">current</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="$count+1"/>
				</a>			
			</xsl:if>
			<xsl:call-template name="pages">
				<xsl:with-param name="count" select="$count + 1"/>
				<xsl:with-param name="max" select="$max"/>
				<xsl:with-param name="url" select="$url"/>
				<xsl:with-param name="urlAppend" select="$urlAppend"/>
				<xsl:with-param name="current" select="$current"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>	

</xsl:stylesheet>
