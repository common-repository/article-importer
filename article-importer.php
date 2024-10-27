<?php
/*
Plugin Name: Article Importer
Plugin URI: http://wordpress.org/plugins/article-importer/
Description: CSVデータから記事を投稿します。
Author: kijitsuku
Author URI: https://kijitsuku.work/
Version: 2.0
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if ( !defined('WP_LOAD_IMPORTERS') ) {
	return;
}

require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require_once $class_wp_importer;
	}
}

if ( class_exists( 'WP_Importer' ) ) {
	class ArticleImporter extends WP_Importer {
		
		function header() {
			echo '<div class="wrap">';
			echo '<h2>Article Importer</h2>';
		}

		function footer() {
			echo '</div>';
		}
		
		function firstMessage() {
			echo '<p>CSVファイルを選択してください。</p>';
			wp_import_upload_form( add_query_arg('step', 1) );
		}

		function import() {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				echo '<p><strong>インポートファイルが不正です。</strong><br />';
				echo esc_html( $file['error'] ) . '</p>';
				return false;
			} else if ( ! file_exists( $file['file'] ) ) {
				echo '<p><strong>インポートファイルが不正です。</strong></p>';
				return false;
			}
			
			$this->id = (int) $file['id'];
			$this->file = get_attached_file($this->id);
			$result = $this->process_posts();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		function process_posts() {

			$handle = fopen($this->file, 'r');
			if ( $handle == false ) {
				echo '<p><strong>ファイルのアップロードに失敗しました。</strong></p>';
				wp_import_cleanup($this->id);
				return false;
			}

			remove_filter('content_save_pre', 'wp_filter_post_kses');

			$roopCount = 0;
			while (($csvData = fgetcsv($handle, 0, ",")) !== FALSE) {

				$roopCount++;

				if(count($csvData) == 8) {
					$Title = $csvData[0];
					$MovieUrl = $csvData[1];
					$ThumbnailUrl = $csvData[2];
					$Category = $csvData[3];
					$Tags = $csvData[4];
					$Remarks = $csvData[5];
					$ArticleDate = $csvData[6];
					$TemplateInfo = $csvData[7];

					$Tags = mb_convert_kana($Tags, 's');
					$allTags = explode( " ", $Tags );
					
					$post_categories = term_exists($Category, 'category');
					if ($post_categories === 0 || $post_categories === null) {
						$post_categories = wp_insert_term( $Category, 'category' );
					}

					$articleImagePath = "";
					
					if($ThumbnailUrl != "") {
						$baseThumbnailName = substr($ThumbnailUrl, strrpos($ThumbnailUrl , "/" ) + 1);
						
						list($file_name, $file_type) = explode("." , $baseThumbnailName);
						$postname = date("YmdHis") . $this->makeRandStr(32);
						$picName = $postname . "." . $file_type;

						$wp_upload_dir = wp_upload_dir();
						
						$options['ssl']['verify_peer']=false;
						$options['ssl']['verify_peer_name']=false;
						$data = file_get_contents($ThumbnailUrl, false, stream_context_create($options));
						
						$imagepath = $wp_upload_dir['path'] . "/" . $picName;
						file_put_contents($imagepath, $data);
						$meta = getimagesize($imagepath);
						$articleImagePath = $wp_upload_dir["url"] . "/" . $picName;

						// ファイルメタ情報登録
						$attachment = array(
						  'post_mime_type' => image_type_to_mime_type($meta[2]),
						  'post_title' => preg_replace('/\.[^.]+$/', '', $imagepath),
						  'post_content' => '',
						  'post_status' => 'inherit'
						);
					}
					
					$TemplateInfo = str_replace("{{{タイトル}}}", $Title, $TemplateInfo);
					$TemplateInfo = str_replace("{{{動画URL}}}", $MovieUrl, $TemplateInfo);
					$TemplateInfo = str_replace("{{{サムネイル画像URL}}}", $articleImagePath, $TemplateInfo);
					$TemplateInfo = str_replace("{{{カテゴリ}}}", $Category, $TemplateInfo);
					$TemplateInfo = str_replace("{{{タグ}}}", $Tags, $TemplateInfo);
					$TemplateInfo = str_replace("{{{備考}}}", $Remarks, $TemplateInfo);
					
					$post = array(
					  'post_status'           => 'publish',
					  'post_type'             => 'post',
					  'post_author'           => $user_id,
					  'ping_status'           => get_option( 'default_ping_status' ),
					  'post_parent'           => 0,
					  'menu_order'            => 0,
					  'to_ping'               => '',
					  'pinged'                => '',
					  'post_password'         => '',
					  'guid'                  => '',
					  'post_content_filtered' => '',
					  'post_excerpt'          => '',
					  'post_date'          => date("Y-m-d H:i:s", strtotime($ArticleDate)),
					  'post_content'          => $TemplateInfo,
					  'post_title'            => $Title,
					  'post_name'             => $postname,
					  'tags_input'             => $allTags,
					);
					
					$post_ID = wp_insert_post( $post, $wp_error );
					
					wp_set_post_categories( $post_ID, $post_categories, false );

					if($ThumbnailUrl != "") {
						$attach_id  = wp_insert_attachment( $attachment, $imagepath, $post_ID);

						if($attach_id) {
							require_once(ABSPATH . "wp-admin" . '/includes/image.php'); // これが必要
							$attach_data = wp_generate_attachment_metadata( $attach_id, $imagepath );
							wp_update_attachment_metadata( $attach_id,  $attach_data );
							$thumbnail = add_post_meta( $post_ID, "_thumbnail_id", $attach_id, false );
						}
					}
				}

				if(count($csvData) == 7) {
					$Title = $csvData[0];
					$Tags = $csvData[1];
					$Category = $csvData[2];
					$ArticleDate = $csvData[3];
					$ThumbnailUrls = $csvData[4];
					$Remarks = $csvData[5];
					$Publicfg = $csvData[6];

					$Tags = mb_convert_kana($Tags, 's');
					$allTags = explode( " ", $Tags );
					
					$post_categories = term_exists($Category, 'category');
					if ($post_categories === 0 || $post_categories === null) {
						$post_categories = wp_insert_term( $Category, 'category' );
					}

					$firstPic = "";
					$loopcount = 0;

					if($ThumbnailUrls != "") {
						$tmpArray = explode( ",", $ThumbnailUrls );
						foreach ($tmpArray as $ThumbnailUrl) {

							if($ThumbnailUrl == "") {
								contonue;
							}

							$baseThumbnailName = substr($ThumbnailUrl, strrpos($ThumbnailUrl , "/" ) + 1);
							
							list($file_name, $file_type) = explode("." , $baseThumbnailName);
							$postname = date("YmdHis") . $this->makeRandStr(32);
							$picName = $postname . "." . $file_type;

							$wp_upload_dir = wp_upload_dir();
							
							$options['ssl']['verify_peer']=false;
							$options['ssl']['verify_peer_name']=false;
							$data = file_get_contents($ThumbnailUrl, false, stream_context_create($options));
							
							$imagepath = $wp_upload_dir['path'] . "/" . $picName;
							file_put_contents($imagepath, $data);
							$meta = getimagesize($imagepath);
							$articleImagePath = $wp_upload_dir["url"] . "/" . $picName;

							if($loopcount == 0) {
								$firstPic = $articleImagePath;
							}

							// ファイルメタ情報登録
							$attachment = array(
							  'post_mime_type' => image_type_to_mime_type($meta[2]),
							  'post_title' => preg_replace('/\.[^.]+$/', '', $imagepath),
							  'post_content' => '',
							  'post_status' => 'inherit'
							);
							
							$Remarks = str_replace($ThumbnailUrl, $articleImagePath, $Remarks);

							$loopcount++;
						}
					}

					$publicval = "";

					if($Publicfg == "2") {
						$publicval = "publish";
					} else {
						$publicval = "private";
					}
					
					$post = array(
					  'post_status'           => $publicval,
					  'post_type'             => 'post',
					  'post_author'           => $user_id,
					  'ping_status'           => get_option( 'default_ping_status' ),
					  'post_parent'           => 0,
					  'menu_order'            => 0,
					  'to_ping'               => '',
					  'pinged'                => '',
					  'post_password'         => '',
					  'guid'                  => '',
					  'post_content_filtered' => '',
					  'post_excerpt'          => '',
					  'post_date'          => date("Y-m-d H:i:s", strtotime($ArticleDate)),
					  'post_content'          => $Remarks,
					  'post_title'            => $Title,
					  'post_name'             => $postname,
					  'tags_input'             => $allTags,
					);
					
					$post_ID = wp_insert_post( $post, $wp_error );
					
					wp_set_post_categories( $post_ID, $post_categories, false );

					if($firstPic != "") {
						$attach_id  = wp_insert_attachment( $attachment, $firstPic, $post_ID);

						if($attach_id) {
							require_once(ABSPATH . "wp-admin" . '/includes/image.php'); // これが必要
							$attach_data = wp_generate_attachment_metadata( $attach_id, $firstPic );
							wp_update_attachment_metadata( $attach_id,  $attach_data );
							$thumbnail = add_post_meta( $post_ID, "_thumbnail_id", $attach_id, false );
						}
					}
				}
			}

			wp_import_cleanup($this->id);

			echo '<p><strong>ファイルのアップロードに成功しました。</strong></p>';
		}

		// dispatcher
		function dispatch() {

			$this->header();
			
			if (empty ($_GET['step'])) {
				$step = 0;
			} else {
				$step = (int) $_GET['step'];
			}

			switch ($step) {
				case 0 :
					$this->firstMessage();
					break;
				case 1 :
					check_admin_referer('import-upload');
					set_time_limit(0);
					$result = $this->import();
					if ( is_wp_error( $result ) ) {
						echo $result->get_error_message();
					}
					break;
			}
			
			$this->footer();

		}

		function makeRandStr($length) {
			$str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
			$r_str = null;
			for ($i = 0; $i < $length; $i++) {
				$r_str .= $str[rand(0, count($str) - 1)];
			}
			return $r_str;
		}

		
	}

	function article_importer() {
		$ArticleImporter = new ArticleImporter();
		register_importer('articleimporter', 'Article Importer', 'CSVファイルをインポートし記事投稿を行います。' , array ($ArticleImporter, 'dispatch'));
	}

	add_action( 'plugins_loaded', 'article_importer' );

}
