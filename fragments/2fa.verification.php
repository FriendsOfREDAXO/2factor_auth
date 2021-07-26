
<form method="post">
    <?php echo $this->getVar('csrfToken')->getHiddenField(); ?>
    <input type="hidden" name="page" value="2factor_auth_verify"/>
    <input type="text" name="rex_login_otp"/>
    <input type="submit" value="Anmelden" />
</form>
