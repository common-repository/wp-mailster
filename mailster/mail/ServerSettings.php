<?php
	/**
	 * @copyright (C) 2016 - 2024 Holger Brandt IT Solutions
	 * @license GNU/GPL, see license.txt
	 * WP Mailster is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License 2
	 * as published by the Free Software Foundation.
	 * 
	 * WP Mailster is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with WP Mailster; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
	 * or see http://www.gnu.org/licenses/.
	 */

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}

class MstServerSettings
{

    public static function getAOLInbox(){
        $server = new stdClass();
        $server->name                       = 'AOL (POP3)';
        $server->server_type                = 0;
        $server->server_host				= 'pop.aol.com';
        $server->server_port				= 995;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 0;
        $server->protocol					= 'pop3';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getAOLSMTP(){
        $server = new stdClass();
        $server->name                       = 'AOL (SMTP)';
        $server->server_type                = 1;
        $server->server_host				= 'smtp.aol.com';
        $server->server_port				= 587;
        $server->secure_protocol			= '';
        $server->secure_authentication	    = 1;
        $server->protocol					= 'smtp';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getGoogleMailInbox(){
        $server = new stdClass();
        $server->name                       = 'Gmail (IMAP)';
        $server->server_type                = 0;
        $server->server_host				= 'imap.gmail.com';
        $server->server_port				= 993;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 0;
        $server->protocol					= 'imap';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getGoogleMailSMTP(){
        $server = new stdClass();
        $server->name                       = 'Gmail (SMTP)';
        $server->server_type                = 1;
        $server->server_host				= 'smtp.gmail.com';
        $server->server_port				= 465;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 1;
        $server->protocol					= 'smtp';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getOneComInbox(){
        $server = new stdClass();
        $server->name                       = 'One.com (IMAP)';
        $server->server_type                = 0;
        $server->server_host				= 'imap.one.com';
        $server->server_port				= 993;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 0;
        $server->protocol					= 'imap';
        $server->connection_parameter		= '/novalidate-cert';
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getOneComSMTP(){
        $server = new stdClass();
        $server->name                       = 'One.com (SMTP)';
        $server->server_type                = 1;
        $server->server_host				= 'send.one.com';
        $server->server_port				= 465;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 1;
        $server->protocol					= 'smtp';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getOutlookInbox(){
        $server = new stdClass();
        $server->name                       = 'Outlook.com (IMAP)';
        $server->server_type                = 0;
        $server->server_host				= 'imap-mail.outlook.com';
        $server->server_port				= 993;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 0;
        $server->protocol					= 'imap';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getOutlookSMTP(){
        $server = new stdClass();
        $server->name                       = 'Outlook.com (SMTP)';
        $server->server_type                = 1;
        $server->server_host				= 'smtp-mail.outlook.com';
        $server->server_port				= 587;
        $server->secure_protocol			= 'tls';
        $server->secure_authentication	    = 1;
        $server->protocol					= 'smtp';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getYahooInbox(){
        $server = new stdClass();
        $server->name                       = 'Yahoo (IMAP)';
        $server->server_type                = 0;
        $server->server_host				= 'imap.mail.yahoo.com';
        $server->server_port				= 993;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 0;
        $server->protocol					= 'imap';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

    public static function getYahooSMTP(){
        $server = new stdClass();
        $server->name                       = 'Yahoo (SMTP)';
        $server->server_type                = 1;
        $server->server_host				= 'smtp.mail.yahoo.com';
        $server->server_port				= 465;
        $server->secure_protocol			= 'ssl';
        $server->secure_authentication	    = 1;
        $server->protocol					= 'smtp';
        $server->connection_parameter		= null;
        $server->api_key1	            	= null;
        $server->api_key2           		= null;
        $server->api_endpoint       		= null;
        return $server;
    }

}
