<?php
use OTPHP\TOTP;

require_once 'vendor/autoload.php';

$secret = "EZUQQQILCA4C4EL7LRV6O5G4SVOT5D7TBGHDARVM3QTWD4PM7LZBVTCLD4VLQBJSFYU2II32A42TZDXNFJ2RJZKNBIJB6V3N6VNIUCY";
$otp = TOTP::create($secret);
$user = rex::getUser();
$otp->setLabel($user->getLogin().'@'.rex::getServername() . ' ('. $_SERVER['HTTP_HOST'] .')');
echo 'The current OTP is: '.$otp->now();
$uri = $otp->getProvisioningUri();
dump($uri);

dump($otp->verify('539450'));

$googleChartUri = $otp->getQrCodeUri();
// echo "<img src='{$googleChartUri}'>";

//rex_view::addJsFile($this->getAssetsUrl('qrious.min.js'))

?>
    <input type="hidden" value="<?php echo $uri; ?>" id="2fa-uri">

    <canvas id="qr-code"></canvas>
    <script src="<?php echo $this->getAssetsUrl('qrious.min.js'); ?>"></script>
    <script>
        new QRious({
            element: document.getElementById("qr-code"),
            value: document.getElementById("2fa-uri").value,
            size: 300
        });
    </script>

<?php
exit();