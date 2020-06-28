JsBarcode(".billet_barcode").init();
function copyLine() {
    var copyText = document.getElementById("linhaDigitavel");
    copyText.select();
    document.execCommand("copy");
}