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

class MstDB extends wpdb
{
    function __construct() {
        parent::__construct( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
    }

    public function query( $query, $executeQueryIfStripped=false, $forceOwnQueryExec=false ) {
        $log = MstFactory::getLogger();
        // If we're writing to the database, make sure the query will write safely.
        if ( ! $this->check_ascii( $query ) || $forceOwnQueryExec ) {
            $stripped_query = $this->strip_invalid_text_from_query( $query );
            // strip_invalid_text_from_query() can perform queries, so we need
            // to flush again, just to make sure everything is clear.
            $this->flush();
            if ( ($stripped_query !== $query) || $forceOwnQueryExec ) {
                $log->error('MstDB->query() Stripped query different');
                $log->error('MstDB->query() Orig Query: '.$query);
                $log->error('MstDB->query() Stripped Q: '.$stripped_query);
                if($executeQueryIfStripped || $forceOwnQueryExec){
                    $log->debug('MstDB->query() Execute query anyhow!');
                    $this->last_query = $query;
                    if ( ! empty( $this->dbh ) ) {
                        $this->result = mysqli_query( $this->dbh, $query );
                    }
                    ++$this->num_queries;

                    // Database server has gone away, try to reconnect.
                    $mysql_errno = 0;

                    if ( $this->dbh instanceof mysqli ) {
                        $mysql_errno = mysqli_errno( $this->dbh );
                    } else {
                        /*
                         * $dbh is defined, but isn't a real connection.
                         * Something has gone horribly wrong, let's try a reconnect.
                         */
                        $mysql_errno = 2006;
                    }

                    $log->debug('MstDB->query() Try to reconnect');
                    if ( empty( $this->dbh ) || 2006 === $mysql_errno ) {
                        if ( $this->check_connection() ) {
                            if ( ! empty( $this->dbh ) ) {
                                $this->result = mysqli_query( $this->dbh, $query );
                            }
                            ++$this->num_queries;
                        } else {
                            $this->insert_id = 0;
                            return false;
                        }
                    }

                    // If there is an error then take note of it.
                    if ( $this->dbh instanceof mysqli ) {
                        $this->last_error = mysqli_error( $this->dbh );
                    } else {
                        $this->last_error = __( 'Unable to retrieve the error message from MySQL' );
                    }

                    if ( $this->last_error ) {
                        $log->error('MstDB->query() Last error: '.$this->last_error);
                        // Clear insert_id on a subsequent failed insert.
                        if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
                            $this->insert_id = 0;
                        }

                        $this->print_error();
                        return false;
                    }

                    if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
                        $return_val = $this->result;
                    } elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
                        $this->rows_affected = mysqli_affected_rows( $this->dbh );

                        // Take note of the insert_id.
                        if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
                            $this->insert_id = mysqli_insert_id( $this->dbh );
                        }

                        // Return number of rows affected.
                        $return_val = $this->rows_affected;
                    } else {
                        $num_rows = 0;

                        if ( $this->result instanceof mysqli_result ) {
                            while ( $row = mysqli_fetch_object( $this->result ) ) {
                                $this->last_result[ $num_rows ] = $row;
                                ++$num_rows;
                            }
                        }

                        // Log and return the number of rows selected.
                        $this->num_rows = $num_rows;
                        $return_val     = $num_rows;
                    }

                    $log->debug('MstDB->query() return_val: '.$return_val);
                    return $return_val;
                }else{
                    $log->debug('MstDB->query() Do not execute non-stripped query');
                    $this->insert_id = 0;
                    return false;
                }
            }else{
                $log->debug('MstDB->query() Stripped query same as original, use regular wpdb');
            }
        }else{
            $log->debug('MstDB->query() Ascii only query, use regular wpdb');
        }

        return parent::query($query);
    }
}