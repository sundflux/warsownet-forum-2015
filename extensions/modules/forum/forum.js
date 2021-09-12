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
 * 2011 Victor Luchits <vic@warsow.net>
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
	$('#new-topic').click(function() {
		Post.getForm('new-topic');
		return false;
	});
	
	// get topic content for smart quote
	$('.getquoted').click(function() {
		Post.getForm($(this).attr('id'));
		return false;
	});	
	
	// get topic content for editing
	$('.getquoted-clean').click(function() {
		$('#topic-content').val('');
		Post.getForm($(this).attr('id'));
		return false;
	});		

	// bind css autohide for overflowing topic title
	$(window).resize(function() {
		Window.resizeTopic();		
	});
	
	$(window).ready(function() {
		Window.resizeTopic();	
	});	
	
	$(document).waitForImages(function() {
		// stupid divs, resize user column	
		$('.tbl-tbody .user').each(function(){
			var parentHeight = $(this).parent().height();
			$(this).height(parentHeight);
		});
	});
		
	// format timestamps
	Dates.format();
	
});

var Dates = {

	format: function() {
		$('.datetime').each(function() {
			var timestamp = $(this).text();
			//var regex = /^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
			var regex = /^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9])[:.]([0-5][0-9])[:.]([0-5][0-9]))?$/;
			var parts = timestamp.replace(regex, "$1 $2 $3 $4 $5 $6").split(' ');
			if(parts.length == 6) {
				var now = new Date();
				var today = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
				var yesterday = new Date(today.getFullYear(),today.getMonth(), today.getDate() - 1, 0, 0, 0);
				var d = new Date(parts[0], parts[1]-1, parts[2], parts[3], parts[4], parts[5]);
				if(d.getTime() >= today.getTime()) {
					$(this).text('Today' + " " + d.toLocaleTimeString());
				} else if(d.getTime() >= yesterday.getTime()) {
					$(this).text('Yesterday' + " " + d.toLocaleTimeString());
				} else {
					// TODO: format date according to user settings (transmitted in hidden div/span/comment?)
				}
			}
		});
	}
	
};

var Window = {

	// Resize topic title
	resizeTopic: function() {
		row = $('.tbl-row').width();
		lastpost = $('.lastpost').width();
		views = $('.views').width();
		posts = $('.posts').width();
		newsize = row-lastpost-views-posts - 80;
	  $('.topic').css('width', newsize+'px'); 
	},
	
	// Get selected text (for smart quote)
	getSelected: function() {
		return (!!document.getSelection)?document.getSelection():(!!window.getSelection)?window.getSelection():document.selection.createRange().text;
	}

};

var BBCode = {

	// Load bbcode editor
	loadEditor: function() {
		$('textarea[name=content]').bbcodeeditor({
			bold:$('.bold'),italic:$('.italic'),underline:$('.underline'),link:$('.link'),quote:$('.quote'),code:$('.code'),image:$('.image'),
			back:$('.back'),forward:$('.forward'),back_disable:'btn back_disable',forward_disable:'btn forward_disable',usize: false,dsize: false,
			exit_warning:false,preview:false
		});
	},
	
	// Toggle preview/edit window
	togglePreview: function() {	
		// Edit window visible, so load preview via ajax
		if($('#edit-view').is(':visible')) {
			$("#title-preview").html($("#topic-title").val());
			
			// Get preview
			$('#loader').show();
			var target = '/forum_topic/preview';
			var content = $('#topic-content').val();
			$.ajax({
				url: target,
				type: 'POST',
				data: 'content='+content,
				success: function(data) {
					// show ajax spinner
					$('#loader').hide();
					
					// apply form data to the page
					$('#preview').html(data);
					$('#preview-view').toggle();
					$('#edit-view').toggle();
				}
			});
		} else {
			// Preview window visible, just toggle to edit view
			$('#preview-view').toggle();
			$('#edit-view').toggle();
			
			// Jump to bottom
			Post.jumpTo('#bottom');
			
			// Focus to form field
			$('textarea:visible:first').focus();
		}
		return false;		
	}

};

var Post = {

	// This might look a bit trickery but we have several cases here to handle:
	// 1) regular 'quote and reply'
	// 2) quote and reply with 'smart selected' text
	// 3) prepending 'smart selected' text to existing post write
	// 4) prepending full quote and reply to existing post write
	getForm: function(target) {
		if($("#post").html() == "" || target != 'new-topic') {
			// Show ajax loader
			$('#loader').show();
			
			// Get empty form or with post content if desierd
			var target = '/'+$("#"+target).attr('href');
			
			// Smart quoting: something was selected, use that
			// content instead of full post content
			if(Window.getSelected()!='') {
				// Get the form if it wasn't loaded yet.
				if($("#post").html() == "") {
					$.ajax({
						url: target,
						success: function(data) {
							// show ajax spinner
							$('#loader').hide();
							
							// apply form data to the page
							$('#post').html(data);
							
							// Clear post content
							$('#topic-content').val('[quote]'+Window.getSelected()+'[/quote]\n\n');
							
							// show it
							$('#post').show();
							
							// swap post topic/reply button with cancel button
							$('#post-form').hide();
							$('#cancel-button').show();
							$('#button-preview').show();
							
							// Focus to form field
							$('textarea:visible:first').focus();
							Post.setCaretToEnd();
							
							// Scroll to post form
							Post.scrollTo('#bottom');
							
							// load bbcode editor
							BBCode.loadEditor();
						}
					});				
					return false;
				} else {
					// The form was loaded and nothing was selected
					$('#topic-content').val($('#topic-content').val() + '[quote]'+Window.getSelected()+'[/quote]\n\n');
					
					// show ajax spinner
					$('#loader').hide();
					
					// show it
					$('#post').show();
					
					// swap post topic/reply button with cancel button
					$('#post-form').hide();
					$('#cancel-button').show();
					$('#button-preview').show();
					
					// Focus to form field
					$('textarea:visible:first').focus();
					Post.setCaretToEnd();
					return false;
				}
			} else {
				// Either full quote or empty reply to
				var postContent = $('#topic-content').val();
				$.ajax({
					url: target,
					success: function(data) {
						// show ajax spinner
						$('#loader').hide();
						
						// apply form data to the page
						$('#post').html(data);
						
						// Don't use old quoted data, allow multiple quotereplys
						if(postContent != 'undefined') {
							$('#topic-content').prepend(postContent);
						}
						
						// show it
						$('#post').show();
						
						// swap post topic/reply button with cancel button
						$('#post-form').hide();
						$('#cancel-button').show();
						$('#button-preview').show();
						
						// Focus to form field
						$('textarea:visible:first').focus();
						Post.setCaretToEnd();
						
						// Scroll to post form
						Post.scrollTo('#bottom');
						
						// load bbcode editor
						BBCode.loadEditor();
					}
				});
				return false;
			}
		} else {
			// Form was already loaded, just show it
			$('#post').show();
			
			// Focus to form field
			$('textarea:visible:first').focus();
			
			// swap post topic/reply button with cancel button
			$('#post-form').hide();
			$('#cancel-button').show();
			
			// Scroll to post form
			Post.scrollTo('#bottom');
		}
		return false;	
	},

	setCaretToEnd: function() {
		// based on http://plugins.jquery.com/node/11435/release
		return $('textarea:visible:first').each(function() {
			$('').focus();
			if(this.setSelectionRange) {
				var len = $(this).val().length * 2;
				this.setSelectionRange(len, len);
			} else {
				$(this).val($(this).val());
			}
			this.scrollTop = 999999;
		});
	},

	// Check that we're not trying to submit emtpy thread.
	checkEmpty: function() {
		if($("#topic-title").val() == "") {
			// Release submit lock
			Forms.allowDouble(); 
		
			alert("Meep! Topic is required.");
			return false;
		}
		if($("#topic-content").val() == "") {
			// Release submit lock
			Forms.allowDouble(); 
			
			alert("Meep! Cannot post empty topic.");
			return false;
		}
		return true;
	},
	
	checkEmptyContent: function() {
		if($("#topic-content").val() == "") {
			// Release submit lock
			Forms.allowDouble(); 
		
			alert("Meep! Cannot post empty topic.");
			return false;
		}
	},
	
	cancelAction: function() {
		// clear form if we're editing the post so we can post to the thread again
		if($('#post_id').val()!='') {
			$("#post").html('');
		}
		$('#post').hide();
		
		// swap cancel button with post topic/reply button
		$('#post-form').show();
		$('#cancel-button').hide();
	},

	scrollTo: function(element) {
		$('html, body').animate({
			scrollTop: $(element).offset().top - 24
		}, 250);
		return false;
	},
	
	jumpTo: function(element) {
		$('html, body').animate({
			scrollTop: $(element).offset().top - 24
		}, 0);
		return false;
	}
};

var Login = {

	getForm: function() {
		// Get login form with ajax (used by forum_topic)
		$('#loader').show();
		var target = '/forum_login';
		$.ajax({
			url: target,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();
				
				// apply form data to the page
				$('#ajax-login-form').html(data);
				
				// Focus to form field
				$('textarea:visible:first').focus();
				
				// Scroll to login form
				Post.scrollTo('#ajax-login-form');
			}
		});
		return false;
	},
	
	submitForm: function(token) {
		// Get login form with ajax (used by forum_topic)
		$('#loader').show();
		var target = '/forum_login/login?session_id='+token;
		var user = $('#user').val();
		var pass = $('#pass').val();
		$.ajax({
			url: target,
			type: 'POST',
			data: 'user='+user+'&pass='+pass,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();
				
				// apply form data to the page
				$('#ajax-login-form').html(data);
				
				// Focus to form field
				$('textarea:visible:first').focus();
				
				// Scroll to login form
				Post.scrollTo('#ajax-login-form');
			}
		});
		return false;
	}	
	
};

var Bookmark = {

	addBookmark: function(id, token) {
		$('#loader').show();
		var target = '/forum_bookmark/add/'+id+'?session_id='+token;
		$.ajax({
			url: target,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();				
				$('#bookmark-img').attr('src','/forum-images/icons/set/book_active.png');
				$('#thread-bookmark').attr('onclick','Bookmark.deleteBookmark('+id+', '+token+')');
			}
		});
		return false;
	},
	
	deleteBookmark: function(id, token) {
		$('#loader').show();
		var target = '/forum_bookmark/delete/'+id+'?session_id='+token;
		$.ajax({
			url: target,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();				
				$('#bookmark-img').attr('src','/forum-images/icons/set/book_inactive.png');
				$('#thread-bookmark').attr('onclick','Bookmark.addBookmark('+id+', '+token+')');
			}
		});
		return false;
	}	
	
};

var Admin = {

	showModal: function(thread_id, forum_id, post_id, page) {
		// Get login form with ajax (used by forum_topic)
		$('#loader').show();
		var target = '/forum_admin/'+thread_id+'/'+forum_id+'/'+post_id+'/'+page;
		$.ajax({
			url: target,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();
				$('#admin-modal').html(data);
				
				// show admin popup as modal
				$('#admin-modal').modal();
			}
		});
		return false;
	},
	
	admin: function() {
		$('#loader').show();
		var target='/forum_admin/actions';
		$.ajax({
			url: target,
			success: function(data) {
				// show ajax spinner
				$('#loader').hide();
				$('#admin-modal').html(data);
				
				// show admin popup as modal
				$('#admin-modal').modal();
			}
		});
		return false;
	},
	
	fetch: function(url, div, modal) {
		$('#loader').show();
		var target='/'+url;
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
	
};

