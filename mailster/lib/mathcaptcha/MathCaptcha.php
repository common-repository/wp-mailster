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
	
	class MstMathCaptcha
	{
		const SESSION_VAR_KEY = 'mst_math_captcha_sec_nr';
		const FORM_VAR_KEY = 'captcha_answer';
		
	/**
	 * This class is based on the code of Constantin Boiangiu
	 * The following is the original copy right header included in his script:
	 * 
	 * 
	 * PHP MATH CAPTCHA
	 * Copyright (C) 2010  Constantin Boiangiu  (http://www.php-help.ro)
	 * 
	 * This program is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 * 
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 **/

	/**
	 * @author Constantin Boiangiu
	 * @link http://www.php-help.ro
	 * 
	 * This script is provided as-is, with no guarantees.
	 */
		public static function getHTML($cssPrefix=''){

			// captcha width
			$captcha_w = 120;
			// captcha height
			$captcha_h = 35;
			// minimum font size; each operation element changes size
			$min_font_size = 11;
			// maximum font size
			$max_font_size = 17;
			// rotation angle
			$angle = 20;
			// background grid size
			$bg_size = 11;
			// path to font - needed to display the operation elements
			$fontType = 'courbd.ttf'; // old, removed now (with Mst V0.4.1)
			$fontType = 'GNUTypewriterStandard.ttf';
			$font_path = $dir = plugin_dir_path( __FILE__ ) . "/" . 'fonts' . "/" . $fontType;
			// array of possible operators
			$operators=array('+','-','*');
			// first number random value; keep it lower than $second_num
			$first_num = rand(1,5);
			// second number random value
			$second_num = rand(6,11);

            $session_var = ''; // will be overwritten below
					
			shuffle($operators);
			$expression = $second_num.$operators[0].$first_num;
			/*
				operation result is stored in session
			*/
			eval("\$session_var=".$second_num.$operators[0].$first_num.";");
			/* 
				save the operation result in session to make verifications
			*/

            $requestId = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));
            set_transient(self::SESSION_VAR_KEY.'_'.$requestId, $session_var, HOUR_IN_SECONDS);
			/*
				start the captcha image
			*/
			$img = imagecreate( $captcha_w, $captcha_h );
			/*
				Some colors. Text is $black, background is $white, grid is $grey
			*/
			$black = imagecolorallocate($img,0,0,0);
			$white = imagecolorallocate($img,255,255,255);
			$grey = imagecolorallocate($img,215,215,215);
			/*
				make the background white
			*/
			imagefill( $img, 0, 0, $white );
			/* the background grid lines - vertical lines */
			for ($t = $bg_size; $t<$captcha_w; $t+=$bg_size){
				imageline($img, $t, 0, $t, $captcha_h, $grey);
			}
			/* background grid - horizontal lines */
			for ($t = $bg_size; $t<$captcha_h; $t+=$bg_size){
				imageline($img, 0, $t, $captcha_w, $t, $grey);
			}
			
			/* 
				this determinates the available space for each operation element 
				it's used to position each element on the image so that they don't overlap
			*/
			$item_space = $captcha_w/3;
			
			/* first number */
			imagettftext(
				$img,
				rand(
					$min_font_size,
					$max_font_size
				),
				rand( -$angle , $angle ),
				rand( 10, $item_space-20 ),
				rand( 25, $captcha_h-25 ),
				$black,
				$font_path,
				$second_num);
			
			/* operator */
			imagettftext(
				$img,
				rand(
					$min_font_size,
					$max_font_size
				),
				rand( -$angle, $angle ),
				rand( $item_space, 2*$item_space-20 ),
				rand( 25, $captcha_h-25 ),
				$black,
				$font_path,
				$operators[0]);
			
			/* second number */
			imagettftext(
				$img,
				rand(
					$min_font_size,
					$max_font_size
				),
				rand( -$angle, $angle ),
				rand( 2*$item_space, 3*$item_space-20),
				rand( 25, $captcha_h-25 ),
				$black,
				$font_path,
				$first_num);
				

        	ob_start(); // do not output image to browser now (activate output buffering)
                	
			imagejpeg($img); // output image
			
        	$imgContent = ob_get_contents();
			
			$imgContent = base64_encode($imgContent);

        	$html = '<img src="data:image/jpeg;base64,';
			$html .= $imgContent;
			$html .= '" alt="" id="math_captcha" />';
			$html .= '<input type="text" name="'.self::FORM_VAR_KEY.'" value="" class="' . $cssPrefix . 'captchaAnswer" />';
			$html .= '<input type="hidden" name="'.self::SESSION_VAR_KEY.'" value="'.$requestId.'" />';

			if (ob_get_level()) {
				ob_end_clean(); // deactivate output buffering
			}
			
			return $html;
		}
		
		public static function getQuestion(){
			return __( 'What is', 'wp-mailster' );
		}
		
		public static function answerCorrect(){
            $log = MstFactory::getLogger();
            $requestId = sanitize_text_field($_REQUEST[self::SESSION_VAR_KEY]);
			$answer = sanitize_text_field($_REQUEST[self::FORM_VAR_KEY]);
  			$correctAnswer = get_transient(self::SESSION_VAR_KEY.'_'.$requestId);
            //$log->debug('MathCaptcha, Answered: '.$answer.', correct Answer: '.$correctAnswer);
  			if($correctAnswer && ($correctAnswer == $answer)){
                  delete_transient(self::SESSION_VAR_KEY.'_'.$requestId);
                  return true;
  			}
			return false;
		}
		
	}
	
