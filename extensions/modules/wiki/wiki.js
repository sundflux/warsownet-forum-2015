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
	// format timestamps
	Dates.format();	
});

// TODO: this is also used by forum, move into a shared .js
var Dates={

	format: function() {
		$('.datetime').each(function() {
			var timestamp=$(this).text();
			var regex=/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
			var parts=timestamp.replace(regex, "$1 $2 $3 $4 $5 $6").split(' ');
			if(parts.length==6) {
				var now=new Date();
				var today=new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
				var yesterday=new Date(today.getFullYear(),today.getMonth(), today.getDate() - 1, 0, 0, 0);
				var d=new Date(parts[0], parts[1]-1, parts[2], parts[3], parts[4], parts[5]);
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