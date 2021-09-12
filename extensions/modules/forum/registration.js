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
	
	$('#username').focusout(function() {
		$('#check').show();
		$.ajax({
			url: 'forum_registration/validate',
			type: 'POST',
			data: 'key=user&value='+$('#username').val(),
			success: function(data) {
				$('#check').hide();
				if(data == 'EXISTS') {
					$('#available').hide();					
					$('#notavailable').show();	
				}
				if(data == 'OK') {
					$('#notavailable').hide();	
					$('#available').show();					
				}
			}
		});
	});
	
	$('#email,#emailconfirm').focusout(function() {
		$('#check-email').show();
		$.ajax({
			url: 'forum_registration/validate',
			type: 'POST',
			data: 'key=email&value='+$('#email').val(),
			success: function(data) {
				$('#check-email').hide();
				if(data == 'EXISTS') {
					$('#available-email').hide();					
					$('#notavailable-email').show();	
				}
				if(data == 'OK') {
					$('#available-email').show();					
					$('#notavailable-email').hide();
				}
			}
		});
	});	
	
	$('#email,#emailconfirm').focusout(function() {
		$('#check-confirm').show();
		$.ajax({
			url: 'forum_registration/validate',
			type: 'POST',
			data: 'key=emailconfirm&value='+$('#emailconfirm').val()+'&email='+$('#email').val()+'&emailconfirm='+$('#emailconfirm').val(),
			success: function(data) {
				$('#check-confirm').hide();
				if(data == 'EXISTS') {
					$('#available-confirm').hide();					
					$('#notavailable-confirm').show();	
				}
				if(data == 'OK') {
					$('#notavailable-confirm').hide();	
					$('#available-confirm').show();					
				}
			}
		});
	});	
	
	$('#password').focusout(function() {
		$('#check').show();
		$.ajax({
			url: 'forum_registration/validate',
			type: 'POST',
			data: 'key=password&value='+$('#password').val(),
			success: function(data) {
				$('#check').hide();
				if(data == 'EXISTS') {
					$('#available').hide();					
					$('#notavailable').show();	
				}
				if(data == 'OK') {
					$('#notavailable').hide();	
					$('#available').show();					
				}
			}
		});
	});		
	
	$('#password2').focusout(function() {
		$('#check-confirm').show();
		$.ajax({
			url: 'forum_registration/validate',
			type: 'POST',
			data: 'key=passwordmatch&value='+$('#password').val()+'&password='+$('#password').val()+'&password2='+$('#password2').val(),
			success: function(data) {
				$('#check-confirm').hide();
				if(data == 'EXISTS') {
					$('#available-confirm').hide();					
					$('#notavailable-confirm').show();	
				}
				if(data == 'OK') {
					$('#notavailable-confirm').hide();	
					$('#available-confirm').show();					
				}
			}
		});
	});		
	
});

