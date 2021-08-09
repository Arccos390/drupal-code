(function ($) {

  Drupal.behaviors.lightbox = {
    attach: function(context, settings) {
        //if($('#bsg-yacht').length) {
          setTimeout(function() { 
            $('a.yacht-gallery').colorbox({
              rel: 'yacht-gallery',
              transition: "elastic", // fade,none,elastic
              width: "75%",
              height: "75%",
              close: "close",
            });
        }, 1500);  
      //}
      
    }
  };

  Drupal.behaviors.remainAshoreYacht = {
    attach: function (context, settings) {
      $(window).on('load', function () {
        let uid = settings.user.uid;
        let user_answer = '';
        let nid = settings.path.currentPath.split('/')[1];
        //console.log(nid)
        // $.get("/get_next_year/" + uid, function (data) {
        //   console.log(data.field_next_year);
        // })
        // .fail(function () {
        //   console.warn("Error calling the get user endpoint");
        // })
        //console.log('testing remain ashore');
        var _this = "";
        $(document).on('click', '#yacht-btn-yes', function () {
          console.log('yes clicked')
          _this = $('#yacht-btn-yes');
          //console.log('yes button clicked');
          var request = $.ajax({
            url: '/update_remain_ashore/' + nid + "/Yes",
            type: 'GET',
            contentType: 'application/json; charset=utf-8'
          });
          request.done(function (data) {
            console.log('Success');
            //$('.lock').addClass('hidden');
            $('.user-action-yacht').each(function(){
              $(this).removeClass('active');
            });
            _this.addClass('active');
          });
          request.fail(function (jqXHR, textStatus) {
            console.log("error");
          });
        });
        //$('#yacht-btn-yes').not('.btn-yes-yacht-processed').addClass('btn-yes-yacht-processed');


        $(document).on('click', '#yacht-btn-no', function () {
          _this = $('#yacht-btn-no');
          var request = $.ajax({
            url: '/update_remain_ashore/' + nid + '/No',
            type: 'GET',
            contentType: 'application/json; charset=utf-8'
          });
          request.done(function (data) {
            console.log('Success');
            $('.user-action-yacht').each(function(){
              $(this).removeClass('active');
            });
            _this.addClass('active');

            //$('.lock').addClass('hidden');
          });
          request.fail(function (jqXHR, textStatus) {
            console.log("error");
          });
        });
        //$('#yacht-btn-yes').not('.btn-no-yacht-processed').addClass('btn-no-yacht-processed');

        $(document).on('click', '#yacht-btn-maybe', function () {
          _this = $('#yacht-btn-maybe');
          var request = $.ajax({
            url: '/update_remain_ashore/' + nid + "/Maybe",
            type: 'GET',
            contentType: 'application/json; charset=utf-8'
          });
          request.done(function (data) {
            console.log('Success');
            //$('.lock').addClass('hidden');
            $('.user-action-yacht').each(function(){
              $(this).removeClass('active');
            });
            _this.addClass('active');
          });
          request.fail(function (jqXHR, textStatus) {
            console.log("error");
          });
        });
        //$('#yacht-btn-yes').not('.btn-maybe-yacht-processed').addClass('btn-maybe-yacht-processed');

      });
    }
  };


  // Drupal.behaviors.test = {
  //   attach: function (context, settings) {
  //     $(window).on('load', function () {
  //       let uid = settings.user.uid;
  //       let user_answer = '';
  //       $.get("/get_next_year/" + uid, function (data) {
  //         console.log(data.field_next_year);
  //         if (data.field_next_year !== null) {
  //           $('.lock').addClass('hidden');
  //         }
  //         else {
  //           $('.lock').removeClass('hidden');
  //         }
  //       })
  //         .fail(function () {
  //           console.warn("Error calling the get user endpoint");
  //         })
  //
  //       $('#btn-yes').not('.btn-yes-test-processed').on('click', function () {
  //         var request = $.ajax({
  //           url: '/set_next_year/' + uid + "/" + 1,
  //           type: 'GET',
  //           contentType: 'application/json; charset=utf-8'
  //         });
  //         request.done(function (data) {
  //           console.log('Success');
  //           $('.lock').addClass('hidden');
  //         });
  //         request.fail(function (jqXHR, textStatus) {
  //           console.log("error");
  //         });
  //       });
  //       $('#btn-yes').not('.btn-yes-test-processed').addClass('btn-yes-test-processed');
  //
  //
  //       $('#btn-no').not('.btn-no-test-processed').on('click', function () {
  //         var request = $.ajax({
  //           url: '/set_next_year/' + uid + "/" + 0,
  //           type: 'GET',
  //           contentType: 'application/json; charset=utf-8'
  //         });
  //         request.done(function (data) {
  //           console.log('Success');
  //           $('.lock').addClass('hidden');
  //         });
  //         request.fail(function (jqXHR, textStatus) {
  //           console.log("error");
  //         });
  //       });
  //       $('#btn-yes').not('.btn-no-test-processed').addClass('btn-no-test-processed');
  //
  //       $('#btn-maybe').not('.btn-maybe-test-processed').on('click', function () {
  //         var request = $.ajax({
  //           url: '/set_next_year/' + uid + "/" + 2,
  //           type: 'GET',
  //           contentType: 'application/json; charset=utf-8'
  //         });
  //         request.done(function (data) {
  //           console.log('Success');
  //           $('.lock').addClass('hidden');
  //         });
  //         request.fail(function (jqXHR, textStatus) {
  //           console.log("error");
  //         });
  //       });
  //       $('#btn-yes').not('.btn-maybe-test-processed').addClass('btn-maybe-test-processed');
  //
  //     });
  //   }
  // };


  $(window).on('load resize', function () {

    if ($(window).width() <= 800) {
      console.log('test');
      $('#calendar .fc-basicWeek-button').on('click', function () {
        $('.datalist-calendar').addClass('is_week');
      });

      $('#calendar .fc-basicDay-button,#calendar .fc-listMonth-button').on('click', function () {
        $('.datalist-calendar').removeClass('is_week');
      });
    }

    if ($(window).width() <= 960) {
      $('.sidebar-menu').addClass('closed');
      $('#leftmenu .menu-item').on('click', function () {
        $('.menu-icon').click();
      });
    }

    $('header .menu-icon').on('click', function () {
      console.log("icon clicked");
      if ($('.sidebar-menu').hasClass('open')) {
        $('.sidebar-menu').removeClass('open').addClass('closed');
      }
      else {
        $('.sidebar-menu').removeClass('closed').addClass('open');
      }
    });
  });


})(jQuery);

// (function($) {
// 	$('#data-table').basictable({
// 	  breakpoint: 730
// 	});
// 	if($(window).width() <= 730){	    	
// 		$('#data-table').basictable('start');    
// 	}    
// })(jQuery);
// (function($) {
// 	$(window).on('load',function(){
// 		$('#data-table').basictable({
// 	      breakpoint: 730
// 	    });
// 		if($(window).width() <= 730){
// 	    	console.log('data-table resp')
// 	    	console.log($('#data-table'));

// 	    	$('#data-table').basictable('start');    
// 	    }    
// 	})

// 	$(window).on('resize',function(){
// 		$('#data-table').basictable({
// 	      breakpoint: 730
// 	    });
// 	    if($(window).width() <= 730){	    	
// 	    	$('#data-table').basictable('start');    
// 	    }    
// 	})            
// })(jQuery);
