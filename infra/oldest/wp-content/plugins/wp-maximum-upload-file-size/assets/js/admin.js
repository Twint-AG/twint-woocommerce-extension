
 (function ($) {

    "use strict";

    $('#hideWmufsNotice').on('click', function(){
       
        $.ajax(
            {
                url: wmufs_admin_notice_ajax_object.wmufs_admin_notice_ajax_url,
                type: 'post',
                dataType: 'json',
                data: {
                    action: 'wmufs_admin_notice_ajax_object_save', data: 1,
                    _ajax_nonce: wmufs_admin_notice_ajax_object.nonce,
                },
                success: function (data) {
                    console.log("success");
                    console.log(data);
                    if (data.success == true) {
                        $('.hideWmufsNotice').hide('fast');
                    }
                },
                error: function (error) {
                    console.log(error);

                }
            }

        )


    });


})(jQuery);