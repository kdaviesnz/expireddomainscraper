
ajaxSEOPool = [];

$(document).ready(function () {

    var page = 1;
    expiredDomainTableSegue(page)(null);

    $('.edsFetchLatest').on('click', expiredDomainFetchLastestSegue());
    

});

var abortAjax = function() {
    console.log('abortAjax() length=' + ajaxSEOPool.length);
    while (ajaxSEOPool.length != 0) {
        ajaxSEOPool[0].abort();
        ajaxSEOPool[0] = null;
        ajaxSEOPool.shift();
    }
}
