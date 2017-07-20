<?php
/*
Plugin Name: Plugin Memorandum
Plugin URI: http://www.warna.info/
Description: Plugin Memorandum makes you can write memo or notes for each plugin in the Plugins page.
Author: jim912
Contributors: jim912, poyosi
Version: 0.1.6
Author URI: http://www.warna.info/
License: GPL2+
Text Domain: plugin-memorandum
Domain Path: /languages/
*/

define( 'PLUGIN_MEMORANDUM_VER', '0.1.6' );

function load_plugin_memorandum_textdomain() {
	// プラグインの翻訳ファイル（日本語ならplugin-memorandum-ja.mo）の読み込み
	load_plugin_textdomain( 'plugin-memorandum', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
// プラグインページのロード（初期設定読み込み完了時）にload_plugin_memorandum_textdomainが実行されるようフックを追加
add_action( 'load-plugins.php', 'load_plugin_memorandum_textdomain' );


function add_memorandum_deactivation_hook() {
	// プラグインの停止時にdelete_memorandum_optionが実行されるようにフック処理を追加
	register_deactivation_hook( __FILE__ , 'delete_memorandum_option' );
}
// 管理画面の起動完了時にadd_memorandum_deactivation_hookが実行されるようフックを追加
add_action( 'admin_init', 'add_memorandum_deactivation_hook' );


function delete_memorandum_option( $network_wide ) {
	// ネットワークで停止されたか否かの判別
	if ( $network_wide ) {
		global $wpdb;
		// ネットワーク管理者向けのメモを削除
		delete_site_option( 'plugin_memorandum' );
		// 作成された子サイトのidを全て取得
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ORDER BY blog_id ASC" );
		// foreachでループ
		foreach ( $blog_ids as $blog_id ) {
			// 各サイトのメモを削除
			delete_blog_option( $blog_id, 'plugin_memorandum' );
		}
	} else {
		// マルチサイトかつネットワーク管理者かどうかを判別
		if ( is_multisite() && is_super_admin() ) {
			// ネットワーク管理者のメモを削除
//			他のサイトで有効化されていないかの検証が必要なので現状実行しない
//			delete_site_option( 'plugin_memorandum' );
		} else {
			// 子サイトのメモを削除
			delete_option( 'plugin_memorandum' );
		}
	}
}


function memorandum_row( $plugin_file, $plugin_data ) {
	global $wp_list_table;

	// テーブルの表示カラム数を取得
	list( $columns, $hidden ) = $wp_list_table->get_column_info();
	// colspanに表示するカラム数を計算
	$colspan = count( $columns );

	// ネットワーク管理者かどうかを判別
	if ( is_super_admin() ) {
		// サイトオプションからメモ内容を取得
		$plugin_memorandum = get_site_option( 'plugin_memorandum' );
	} else {
		// 子サイトのオプションからメモ内容を取得
		$plugin_memorandum = get_option( 'plugin_memorandum' );
	}
	// 表示するプラグインのメモ内容を抜き出し。メモがなければ空
	$memo = isset( $plugin_memorandum[$plugin_file] ) ? $plugin_memorandum[$plugin_file] : '';
	// cookieの保存名を取得
	$cookie_id = urldecode( sanitize_title( $plugin_data['Name'] ) );
	// cookieにメモが開いている状態の設定がなければ、hiddenのclassを出力するように
	$hidden_class = isset( $_COOKIE['pluginMemo'][$cookie_id] ) ? '' : ' hidden';
	// メモ行の表示
?>
<tr class="plugin_memorandum_text<?php echo $hidden_class; ?>">
	<td colspan="<?php echo esc_attr( $colspan ); ?>">
		<textarea name="plugin_memo[<?php echo $plugin_file ?>]" class="plugin-memo" cols="70" rows="2"><?php echo esc_html( $memo ); ?></textarea>
	</td>
</tr>
<?php
}
// プラグイン毎の行表示終了時にmemorandum_rowが実行されるようフックを追加
add_action( 'after_plugin_row', 'memorandum_row', 10, 2 );


function add_memorandum_script() {
	// anti CMS tree view. remove cms tree view's script que
	wp_dequeue_script( 'jquery-cookie' );
	// jQuery cookieの読み込み登録
	wp_enqueue_script( 'jquery-cookie', plugin_dir_url( __FILE__ ) . 'jquery.cookies.js' , array('jquery'), '2.2.0', true);
	// プラグインのscript読み込み登録
	wp_enqueue_script( 'plugin-memorandum', plugin_dir_url( __FILE__ ) . 'plugin-memorandum.js' , array('jquery'), PLUGIN_MEMORANDUM_VER, true);
	// プラグインのcss読み込み登録
	wp_enqueue_style( 'plugin-memorandum', plugin_dir_url( __FILE__ ) . 'plugin-memorandum.css' , array(), PLUGIN_MEMORANDUM_VER);
}
// プラグインページのロード（初期設定読み込み完了時）にadd_memorandum_scriptが実行されるようフックを追加
add_action( 'load-plugins.php', 'add_memorandum_script' );


function add_memorandum_switch_column( $columns ) {
	// メモの開閉スイッチカラムの追加__()が翻訳関数
	$columns['memo-switch'] = __( 'Memo', 'plugin-memorandum' );
	return $columns;
}
// プラグイン一覧のカラム取得時にadd_memorandum_switch_columnのフィルターが実行されるようにフックを追加
add_filter( 'manage_plugins_columns', 'add_memorandum_switch_column' );
add_filter( 'manage_plugins-network_columns', 'add_memorandum_switch_column' );


function memorandum_switch_column( $column_name, $plugin_file, $plugin_data ) {
	// メモの開閉スイッチカラムかどうかを検証
	if ( $column_name == 'memo-switch' ) {
		// ネットワーク管理者かどうかを判別
		if ( is_super_admin() ) {
			// サイトオプションからメモ内容を取得
			$plugin_memorandum = get_site_option( 'plugin_memorandum' );
		} else {
			// 子サイトのオプションからメモ内容を取得
			$plugin_memorandum = get_option( 'plugin_memorandum' );
		}
		// 表示するプラグインのメモ内容を抜き出し。メモがなければ空
		$memo = isset( $plugin_memorandum[$plugin_file] ) ? $plugin_memorandum[$plugin_file] : '';
		$prefix = preg_replace( '/[\n\s]*/', '', $memo ) ? 'commented-' : '';
		// cookieの保存名を取得
		$cookie_id = urldecode( sanitize_title( $plugin_data['Name'] ) );
		// 画像パスの設定。まずはパスの共通部分を定義
		$src = plugin_dir_url( __FILE__ ) . 'images/' . $prefix . 'switch-';
		// メモの開閉状態に応じて、表示する画像ファイルを変更し、パスと結合
		if ( isset( $_COOKIE['pluginMemo'][$cookie_id] ) ) {
			$src .= 'close.png';
		} else {
			$src .= 'open.png';
		}
		// リンクおよび画像の表示
		echo '<a href="#"><img src="' . esc_url( $src ) . '" class="plugin_memorandum_switch" width="22" height="22" alt="" /></a>';
	}
}
// デフォルト表示以外のカスタムカラム表示にmemorandum_switch_columnが実行されるようフックを追加
add_action( 'manage_plugins_custom_column', 'memorandum_switch_column', 10, 3 );


function update_plugin_memorandum() {
	// ユーザー権限の検証。権限がなければ処理を停止
	if ( !current_user_can('activate_plugins') ) {
		wp_die( __( 'You do not have sufficient permissions to manage plugins for this site.' ) );
	}
	
	// メモの更新ボタンが押されていれば実行
	if ( isset( $_POST['update-memo'] ) ) {
		// 送信データの正当性の検証
		check_admin_referer( 'bulk-plugins' );
		// インストールされている全てのプラグインを取得
		$installed_plugins = get_plugins();
		// ネットワーク管理者かどうかを判別
		if ( is_super_admin() ) {
			// サイトオプションからメモ内容を取得
			$plugin_memorandum = get_site_option( 'plugin_memorandum' );
		} else {
			// 子サイトのオプションからメモ内容を取得
			$plugin_memorandum = get_option( 'plugin_memorandum' );
		}
		// メモ内容がまったく無ければメモ内容は空の配列とする（インストール直後の対策）
		$plugin_memorandum = $plugin_memorandum ? $plugin_memorandum : array();
		// 送信されたメモの内容をアンエスケープ（WordPressは勝手にaddslashesしやがるので）
		$post_memo = stripslashes_deep( $_POST['plugin_memo'] );
		// 保存されていたメモ内容と送信されたメモ内容のマージ
		$plugin_memorandum = array_merge( $plugin_memorandum, $post_memo );
		// インストールされているプラグインのみのメモ内容を残す
		$plugin_memorandum = array_intersect_key( $plugin_memorandum, $installed_plugins );
		// ネットワーク管理者かどうかを判別
		if ( is_super_admin() ) {
			// サイトオプションとして、メモ内容を保存
			update_site_option( 'plugin_memorandum', $plugin_memorandum );
		} else {
			// 子サイトのオプションとして、メモ内容を保存
			update_option( 'plugin_memorandum', $plugin_memorandum );
		}

	}
}
// プラグインページのロード（初期設定読み込み完了時）にupdate_plugin_memorandumが実行されるようフックを追加
add_action( 'load-plugins.php', 'update_plugin_memorandum' );


function buffer_start() {
	// 管理画面のフッター表示の際にadd_plugin_memo_submit_buttonが実行されるようフックを追加
	add_action( 'in_admin_footer', 'add_plugin_memo_submit_button' );
	// バッファリング（表示出力を停止して、メモリ内に格納）を開始
	ob_start();
}
add_action( 'admin_head-plugins.php', 'buffer_start' );


function add_plugin_memo_submit_button() {
	// バッファリングを停止して、メモリ内に格納しておいたhtmlのソースを取得
	$content = ob_get_clean();
	// 置換処理を行い、メモ更新ボタンを追加
	$content = str_replace( '<div class="alignleft actions">', '<input type="submit" name="update-memo" class="button-primary" value="' . __( 'Update Memo', 'plugin-memorandum' ) . '" /><div class="alignleft actions">', $content );
	// 溜め込んでおいたソースを出力
	echo $content;
}


// anti CMS Tree Page View hook いらないフックを削除する
function remove_cms_tree_view_hook() {
	global $pagenow;
	if ( $pagenow == 'plugins.php' ) {
		remove_action( 'admin_init', 'cms_tpv_admin_init' );
	}
}
if ( is_admin() ) {
	add_action( 'init', 'remove_cms_tree_view_hook', 0 );
}