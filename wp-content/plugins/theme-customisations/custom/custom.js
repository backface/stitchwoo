
jQuery(document).ready(function($){
	if ($("h1.product_title").html() === "My Costum Embroidered Product") {
		$('body').on('click', '.component_option_thumbnail', function(e) {
			title = ($(this).parents(".component_inner").prev().find(".step_title").html());
			img_prod =  $(".woocommerce-product-gallery__image").find("img");

			if (title === "Carrier product" || title === "carrier") {
				img_pattern = $(this).parents(".component").prev().find(".selected").find("img");
				img_carrier = $(this).find("img");
				updateProductImage(img_prod, img_carrier, img_pattern);
			}  else  {
				 if ($(this).parents(".component").next().find(".selected").length) {
					img_carrier = $(this).parents(".component").next().find(".selected").find("img");
					img_pattern = $(this).find("img");
					updateProductImage(img_prod, img_carrier, img_pattern);
				} else alert("not find");
			}
		});				
	}	
});
			
function updateProductImage(img_prod, img_car, img_pat ) {
	var ajax = new XMLHttpRequest()
	var params = "?a=" + img_car.attr("src") + "&b=" + img_pat.attr("src") +  "&c=" + img_prod.attr("src");

    ajax.onreadystatechange = function () {
		if (ajax.readyState == 4 && ajax.status == 200) {
			//showResults(JSON.parse(ajax.responseText));
			//alert(ajax.responseText);
			//result = jQuery("body").append('<img src="data:image/png;base64,' + ajax.responseText + '"/>');

			jQuery(".woocommerce-product-gallery__image").find("img").attr("src",
				img_prod.attr("src") + "?t=" + new Date().getTime());

			jQuery(".woocommerce-product-gallery__image").attr("data-thumb",
				img_prod.attr("src") + "?t=" + new Date().getTime());

			jQuery(".attachment-shop_single").attr("src",
				img_prod.attr("src") + "?t=" + new Date().getTime());

			jQuery(".attachment-shop_single").attr("large-image",
				img_prod.attr("src") + "?t=" + new Date().getTime());

			jQuery(".attachment-shop_single").parent().attr("href",
				img_prod.attr("src") + "?t=" + new Date().getTime());

			jQuery(".attachment-shop_single").attr("data-src",
				img_prod.attr("src") + "?t=" + new Date().getTime());

			jQuery(".attachment-shop_single").attr("srcset",
				img_prod.attr("src") + "?t=" + new Date().getTime())

			jQuery(".ssatc-sticky-add-to-cart").find("img").attr("src",
				img_prod.attr("src") + "?t=" + new Date().getTime());
		}
	}

    ajax.open('GET', '/updateProductImage.php' + params)
    ajax.send();
}
