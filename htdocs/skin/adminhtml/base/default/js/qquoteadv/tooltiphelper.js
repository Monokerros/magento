/**
 * Created by Bram van Dooren on 12-02-14.
 */

document.observe("dom:loaded", function () {
    $$(".field-tooltip").invoke('observe', 'mouseover', function (event) {
        var articleId = getArticleId(this);
        if (articleId !== undefined) {
            getArticleContent(this, articleId);
        }
    });

    $$(".field-tooltip").invoke('observe', 'click', function (event) {

    });

    function getArticleContent(tooltipElement, articleId) {
        var url = "https://cart2quote.zendesk.com/api/v2/topics/" + articleId + ".json";
        var http_request = new XMLHttpRequest();
        try {
            // Opera 8.0+, Firefox, Chrome, Safari
            http_request = new XMLHttpRequest();
        } catch (e) {
            // Internet Explorer Browsers
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {
                    // Something went wrong
                    alert("Your browser does not support this.");
                    return false;
                }
            }
        }
        http_request.onreadystatechange = function () {
            if (http_request.readyState == 4) {
                if (http_request.status == 200) {
                    var jsonObj = JSON.parse(http_request.responseText);
                    setArticleContent(tooltipElement, jsonObj['topic']['body']);
                }
                else {
                    setArticleContent(tooltipElement, "No help message found.");
                    s
                }
            }
        }
        http_request.open("GET", url, true);
        http_request.send();
    }

    function setArticleContent(tooltipElement, body) {
        tooltipElement.firstChild.innerHTML = body;
    }

    function getArticleId(tooltipElement) {
        var articleId;
        var articleIdInputElement = tooltipElement.getElementsByClassName('article_id')[0];
        if (articleIdInputElement !== undefined) {
            articleId = articleIdInputElement.value;
        }
        return articleId;
    }

});