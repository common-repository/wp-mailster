//
// @copyright (C) 2016 - 2024 Holger Brandt IT Solutions
// @license GNU/GPL, see license.txt
// WP Mailster is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License 2
// as published by the Free Software Foundation.
//
// WP Mailster is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with WP Mailster; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
// or see http://www.gnu.org/licenses/.
//





jQuery(document).ready(function() {

    jQuery("form.admin-table-with-custom-checks #doaction, form.admin-table-with-custom-checks #doaction2").click(function(event) {
        console.log(event);
        console.log(jQuery(this));

        var selectedAction = jQuery(this).attr("id").substr(2); // top or bottom?
        var actionType = jQuery('select[name="'+selectedAction+'"]').val(); // actual selected action

        var doConfirm = false;
        var dialog = '';
        if(actionType == 'bulk-delete'){
            doConfirm = true;
            dialog = '#dialog-confirm-delete';
        }
        if(actionType == 'bulk-clear-queue'){
            doConfirm = true;
            dialog = '#dialog-confirm-clear';
        }


        if(doConfirm){
            event.preventDefault();
            console.log(jQuery(dialog));
            jQuery(dialog).dialog({
                    modal: true, title: jQuery(dialog).attr('title'), draggable: false, autoOpen: true, dialogClass: 'wp-dialog',
                    width: 'auto', resizable: false,
                    position: {
                        my: "center",
                        at: "center",
                        of: window
                    },
                    buttons: {
                        Yes: function () {
                            jQuery(this).dialog("close");
                            jQuery('form.admin-table-with-custom-checks').submit();
                        },
                        No: function () {
                            jQuery(this).dialog("close");
                        }
                    },
                    open: function(event, ui) {
                        jQuery(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
                    },
                    close: function (event, ui) {
                        jQuery(this).dialog("close");
                    }
                });
        }

    });

});