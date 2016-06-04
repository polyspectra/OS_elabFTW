<?php
/**
 * index.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Index page
 *
 */
use \Elabftw\Elabftw\Tools as Tools;

require_once 'inc/common.php';
//$page_title = _('Home');
$selected_menu = null;

// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {


}


require_once 'inc/head.php';

$formKey = new \Elabftw\Elabftw\FormKey();


// if we are not in https, die saying we work only in https
if (!Tools::usingSsl()) {
    // get the url to display a link to click (without the port)
    $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='$url'>$url</a>";
    display_message('error', $message);
    require_once 'inc/footer.php';
    exit;
}
?>


<section class='center'>
    <p>
    	<h2><?php echo _('OpenSauce = Open Source Chemistry'); ?></h2>
        <br>
        <h3> A powerful platform for chemical collaboration. Explore, adapt, document and share actionable recipes with the world. Open Sauce is built on the eLabFTW electronic lab notebook software, so you can quickly record, analyze and timestamp your experiments.</h3>
        <br>
    </p>
    <h4> OpenSauce.us is currently in a public beta.<br> We would greatly appreciate your feedback via our <a href='https://github.com/rawray7/opensauce_elabftw/issues'> GitHub issue tracker </a>. </h4>
    <br>
    <h4><a href='login.php'> Sign in to your account. </a> </h4>
    <h4> Don't have an account? <a href='register.php'> Register </a> now! </h4>
</section>

<script>
// Check for cookies
function checkCookiesEnabled() {
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;
    if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) {
        document.cookie="testcookie";
        cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
    }
return (cookieEnabled);
}
if (!checkCookiesEnabled()) {
    var cookie_alert = "<div class='errorbox messagebox<p><?php echo _('Please enable cookies in your navigator to continue.'); ?></p></div>";
    document.write(cookie_alert);
}
</script>





<?php require_once 'inc/footer.php';