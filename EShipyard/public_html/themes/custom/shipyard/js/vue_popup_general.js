// let close_popup = false;

// Vue.component('bsg-popup', {
//   template: 
//   `
//   <div class="lock" v-if="!this.close_popup">
//   <div class="user-popup">
    
//     <h2>Welcome to our new web application “E-Shipyard” !</h2>
    
//     <div class='subheader'>You are kindly requested to let us know if you wish to reserve a place in our Yard for the following winter 2019 – 2020:</div>
//     <div class='popup-actions-wrapper'>
//       <button @click="sendChoice('yes')" class="user-action">YES</button> 
//       <button @click="sendChoice('no') " class="user-action">NO</button>
//       <button @click="sendChoice('dunno')" class="user-action">MAYBE</button>
//     </div>

//     <div class="popup-misc">
//     <p class="popup-misc-explain">This information is very important as it will allow us to know the number of the new boats we can book for next winter.</p>
//     <p class="popup-misc-gain-access">Only after you answer the question, you will <span class="bold">gain full access to your account</span> where you will be able to check our availability in real time and book a date for your next launching or hauling.</p>
//     </div>
//   </div>
//   </div>  
//   `,

//   data: function() {
//     return {
//       getUser: '/api/list/cradles?_format=json',
//       setUser: 'get_next_year/1'
//     }
//   },
//   mounted: function() {
//     (function($) {
//        Drupal.behaviors.test = {
//         attach: function (context, settings) {
//           $(document).ready(function(){
//             let uid = settings.user.uid;
//             let user_answer = $.get( "/get_next_year/"+uid, function(data) {
//               if(data.field_next_year !== null) {
//                 $('.lock').hide();
//               }
//             })
//             .fail(function() {
//               console.warn( "Error calling the get user endpoint" );
//             })
//             // if(parseInt(uid) === 18) {
//             //   
//             // }
//           });          
//         }
//       };
//     })(jQuery); 
//   },
//   methods: {
//     sendChoice: function(choice){
//       console.log(choice);
//       this.close_popup = true;
//     }
//   }
// })