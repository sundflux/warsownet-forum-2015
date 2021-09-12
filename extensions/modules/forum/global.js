/**
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 * 
 * The contents of this file are subject to the Mozilla Public License Version 
 * 1.1 (the "License"); you may not use this file except in compliance with 
 * the License. You may obtain a copy of the License at 
 * http://www.mozilla.org/MPL/
 * 
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 * 
 * The Original Code is Copyright (C) 
 * 2011 Tarmo Alexander Sundström <ta@sundstrom.im>
 * 
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer. All Rights Reserved.
 * 
 * Contributor(s):
 * 
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 */
 
$(function() {

	// get topic form with ajax. also works without.
	$('#forum-search').click(function() {
		if($('#search-box').is(":visible")) {
			$('#search-box').hide();
			return false;
		}
		var target = $("#forum-search").attr('href');
		$.ajax({
			url: target,
			success: function(data) {
				// apply form data to the page
				$('#search-box').html(data);
				$('#search-box').slideDown('fast');
				
				// Focus to form field
				$('#search-field').focus();
			}
		});		
		return false;
	});
	
	// Tables
	$('#table').ready(function() {
		Table.Colorize();
	});	
	
	$('#messagecontainer div').click(function() {
		$(this).fadeOut();
	});
	
	if($('#messagecontainer').length !== 0) {
		setTimeout("$('#messagecontainer').fadeOut();",2000);
	}	
	
	Forms.preventDouble();

});

var Forms = {

	preventDouble: function() {
		// Prevent double-submitting forms
		$('form').submit(function(){
			$(':submit', this).click(function() {
				return false;
			});
		});
	}, 

	allowDouble: function() {
		// Release double-submitting lock
		$('form').unbind();
		
		// Re-bind the event
		Forms.preventDouble();
	},
	
	fetch: function(url, div, modal) {
		$('#loader').show();
		var target = url;
		$.ajax({
			url: target,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();
				$(div).html(data);
				
				// show admin popup as modal
				if(modal) {
					$(div).modal();
				}
			}
		});
		return false;
	}	
	
}

var Search = {

	search: function() {
		$('#search-loader').show();
		var searchString = $('#search-field').val();
		var forumID = $('#forum_id').html();
		
		$('#search-field').attr("disabled", "disabled");
		$('#search-btn').attr("disabled", "disabled");
		$.ajax({
			url: "/forum_search/doSearch/"+forumID,
			global: false,
			type: "POST",
			data: ({search : searchString}),
			dataType: "html",
			async: false,
			success: function(msg) {
				$('#search-loader').hide();
				$('#search-result').html(msg);
				$('#search-result').slideDown('fast');
				
				// enable fields again
				$('#search-field').removeAttr("disabled");
				$('#search-btn').removeAttr("disabled");
				
				// focus back on the search
				$('#search-btn').focus(); // firefox fix, focus on other element before refocus
				$('#search-field').focus();
			}
		});
	},
	
	// Enable/disable dropdown box in extended search
	toggleMode: function() {
		if($('#forum-select-list').attr('disabled') == "") {
			$('#forum-select-list').attr('disabled', 'disabled');
		} else {
			$("input[type='checkbox']").attr('checked', false);
			$('#forum-select-list').removeAttr('disabled');
		}
	}	

};

var Table={

	// Odd/even colorize tables
	Colorize: function() {
		$('.tbl-tbody .tbl-row:odd').addClass('alt');
		$('.tbl-tbody .tbl-row .user:even').addClass('alt2');
		$('.tbl-tbody .tbl-row .user:odd').addClass('alt3');	
	}
		
}

// ping every 15 mins to keep session alive
var Ping=function() {
	$.ajax({
		url : '/forum/ping',
		complete : function () {
			setTimeout(function () {
				Ping();
			}, 900000);
		}
	});
};

setTimeout(function () { 
	Ping(); 
}, 900000);
