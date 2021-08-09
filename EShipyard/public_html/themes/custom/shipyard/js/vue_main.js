apiMenuUrl = "http://bsg.e-shipyard.gr/api/menu_items/main-menu";
let date_counter = [];
let requests = [];
let field_yacht_type;

Vue.component('bsg-yacht', {
  props: ['data'], 
  template: `<div class="yacht-card-container">
  <div class="yacht-card-header">
    <div class="yacht-card flag" :class="data[0].nationality.toLowerCase()"></div>
    <div class="yacht-card name-wrapper">
      <div :id="data[0].field_yacht_type.replace('/','').toLowerCase()" class="yacht-card type"></div>
      <div class="yacht-card name">
        <h3>{{ data[0].title }}</h3>
      </div>  
    </div>    
  </div>
  <div class="yacht-card-main-info">
      <div class="info-col left">
       <div class="info-col-wrapper">
          <div id="loa" class="info-text">
            <strong><div class='info-title'> LOA </div></strong>
            <span>{{ data[0].LOA }} </span>
          </div>
        </div>
      </div>
       <div id="weight" class="info-col">
        <div class="info-text">
          <strong><div class='info-title'> Weight </div></strong>
          <span>{{ data[0].Weight }} </span>
        </div>
      </div>
       <div class="info-col right">
        <div class="info-col-wrapper">
          <div id="draft" class="info-text">
            <strong><div class='info-title'> Draft </div></strong>
            <span>{{ data[0].Draft }} </span>
          </div>
        </div>
      </div>
    </div>
  <div class="yacht-card-secondary-info">
    <strong><span class="info-text left">Cradle: {{ data[0].field_entity_ref_cradle }} </span></strong>
    <strong><span class="info-text right">Area: {{ data[0].Area }}</span></strong>
  </div>
  <div class="yacht-card-footer">
    <div class="file licence left"><a :href="data[0].BoatLicense" target="_blank">Boat Licence</a></div>
    <div class="file insurance right"><a :href="data[0].BoatInsurance" target="_blank">Boat Insurance</a></div>
  </div>
  <a class='btn btn-edit' :href="data[0].edit_node">Edit Ship</a></div> `
})

Vue.component('bsg-yacht-inf', {
  props: ['data'], 
  data: function(){
    return {
      tabInfo: this.data,
      isActive: {"images": true, "files": false,"logs": false}
    }
  },
  mounted: function() {
    //this.tabInfo = `<div class="yacht-tab-image"><img src="${this.data[0].yacht_images}"/></div>`;
    this.changeTabData('images');
    document.getElementById('general-container').style.display = 'block';
  },
  methods: {
    changeTabData: function(data) {      
      this.tabInfo = '';

      if(data === 'images'){
        this.isActive.logs = false;
        this.isActive.files = false;
        this.isActive.images = true;
        this.tabInfo = '';
        var file_images = this.data[0].yacht_images.split(',');
        console.log('file_images', file_images);
        if(file_images[0] !== "") {
          for(var i=0; i<file_images.length; i++) {
            this.tabInfo += `<div class="yacht-tab-image"><a class="yacht-gallery" href="${file_images[i]}"><img src="${file_images[i]}"/></a></div>`;
          }          
        }
      } 
      else if( data === 'files') {
        this.isActive.logs = false;
        this.isActive.images = false;
        this.isActive.files = !this.isActive.files;
        var mod = '';
        var file_urls = this.data[0].yacht_files.split(',');
        for(var i=0; i<file_urls.length; i++) {
          var filename = file_urls[i].split('/').pop();
          
          if(i % 2 === 0) {
            if(i === 0) {
              mod = 'even first';
            }
            else if(i === (file_urls.length-1)) {
              mod = 'even last';
            }
            else {
              mod = 'even';  
            }            
          }
          else {
            if(i === file_urls.length) {
              mod = 'odd last';
            }
            else {
              mod = 'odd';  
            }            
          }
          this.tabInfo += `<div class='yacht-tab-file file-${mod}'><a href="${file_urls[i]}">${filename}</a></div>`;  
        }        
      }
      else {
        this.isActive.files = false;
        this.isActive.images = false;
        this.isActive.logs = !this.isActive.logs;
        console.log(this.data);

        //this.tabInfo = `There are no Cradle log data yet`;
        
        let apiUrl = `/get_cradle_log_history/${this.data[0].nid}?_format=json`;
        let resp = "";
        axios.get(apiUrl)
              .then(response => { resp = response.data; 
              
                var mod = '';
                let cradle_data = [];
                //var resp = this.data[0].yacht_files.split(',');
                if(resp.length !== 0) {
                  for(var i=0; i<resp.length; i++) {
                    var filename = resp[i].cradle_title;
          
                    if(i % 2 === 0) {
                      if(i === 0) {
                        mod = 'even first';
                      }
                      else if(i === (resp.length-1)) {
                        mod = 'even last';
                      }
                      else {
                        mod = 'even';  
                      }            
                    }
                    else {
                      if(i === resp.length) {
                        mod = 'odd last';
                      }
                      else {
                        mod = 'odd';  
                      }            
                    }
                    this.tabInfo += `<div class='yacht-tab-file file-${mod}'>${filename}</div>`;  
                
                  }
                }
                else {
                  this.tabInfo = 'No Available Cradle Data yet.'
                }
              
              }) //this.nodeData = response.data
              .catch(e => { this.errors.push(e) })      
      }
    }
  },
  template: `<div class='bsg-yacht-infotabs'>
      <div class="tab tab-info " :class="{active: this.isActive.images}" @click='changeTabData("images")'>Images</div>
      <div class="tab tab-info " :class="{active: this.isActive.files}" @click='changeTabData("files")'>Files</div>
      <div class="tab tab-info " :class="{active: this.isActive.logs}" @click='changeTabData("logs")'>Cradle Log</div>
      <div class="info-content" v-html="tabInfo"> </div>
    </div>`
})

const request = Vue.component('bsg-yacht-requests', {
  props:['data'],
  template: `
  <div class="yacht-requests">
    
    <div class="yacht-requests-header">
      <h4>REQUESTS</h4>
      <div>Check the scheduled actions or select one, according to the available dates that are shown in the calendar, which will be shown after you press “Add”.  </div>

    <div class="yacht-requests-content remain-ashore">
      <span style="display: block;margin: 0 auto; text-align: center; font-weight: 500;">Next winter ( ${new Date().getFullYear()} – ${new Date().getFullYear() + 1} ) Reservation Status: </span>
      <div class="popup-actions-wrapper">
        <button id="yacht-btn-yes" class="user-action-yacht" :class="{active:active === 'yes'}">YES</button> 
        <button id="yacht-btn-no" class="user-action-yacht" :class="{active:active === 'no'}">NO</button>
        <button id="yacht-btn-maybe" class="user-action-yacht" :class="{active:active === 'maybe'}">MAYBE</button>
      </div>
      
      </div>
    </div>

    <div class="yacht-requests-content">
      <div class="latest-requests">
        <div v-if="requestsList && requestsList[0]" style="padding:0;">
          <div v-for="request in requestsList">
            <span class="request-type">{{ request.type }}</span>
            <span class="request-date">{{ new Date(request.suggested_date).toLocaleDateString('el-GR') }}</span>        
            <a v-if="request.status !== 'Completed'" class="request-edit" :href="request.edit_yacht_action">Edit</a>
            <span class="request-status">{{ request.status }}</span>            
          </div>
        </div>
        <div v-else>
          <span class="no-requests"> There are no requests yet for this yacht. </span>
        </div>
      </div>
      <div class="yacht-requests-actions">
        <div class="action action-hauling" :class="{active: this.isActive.hauling}" @click=" (isActive.hauling == true) ? sendRequestPopUp('hauling') : ''"><span class="title">Hauling</span><span class="action-button">Add</span></div> <!-- <a href="#" v-if="this.isActive.hauling"></a> href="/admin/content/yacht-action/add" -->
        <div class="action action-launching" :class="{active: this.isActive.launching}" @click=" (isActive.launching == true) ? sendRequestPopUp('launching') : ''"><span class="title">Launching</span><span class="action-button">Add</span></div> <!-- <a href="#" v-if="this.isActive.launching"></a> href="/admin/content/yacht-action/add"-->
      </div>
    </div>    

    <!-- SEND REQUEST POPUP -->
    <transition name="fade" mode="out-in">
      <div class="send-request" v-if="send_request">
        <div class="calendar-close close-btn" @click="closeRequest"> x </div>
        <div class="send-request-message" style="display: none;"></div>
        <div class="send-request-info" v-html="request_message"></div>
        <div class="send-request-wrapper">
          <div class="send-request-misc-info">
            <label>Yacht Name:</label>
            <div class="yacht-name"> {{ data[0].title }} </div>
          </div>
        <div class="send-request-content">    
          <div v-if="this.type_is_launching">
            <div class="request-date-wrapper">
              <label>Request Date <span style='color:red;'>*</span>: </label>
              <div class="input-wrapper"><input id="request-date-val" type="text" @click="calendarToggle('request','launching')" readonly required></input><div class="calendar-icon"></div></div>
              <span class="help-text">Please select your preferable launching/hauling date and you will be notified with our response</span>
             <!-- @click=" (is_responsive.filters == true && is_open.filters == false) ? test('filters') : (is_open.filters == is_responsive.filters == true) ? close('filters') : '' "-->
            </div>
            <div class="request-date-wrapper">
              <label>Arrival Date <span style='color:red;'>*</span>:</label>
              <div class="input-wrapper"><input id="arrival-date-val" type="text" @click="calendarToggle('arrival','launching')" readonly required></input><div class="calendar-icon"></div></div>
              <span class="help-text">Please select the date you will be arriving at the shipyard</span>
              <!-- <div v-if="this.date_is_shown" id="calendar-send"></div>-->
            </div>            
            <div id="date-popup" style="display:none;">
              <div id="date-popup-arrival">Arrival Date</div>
              <div id="date-popup-request">Request Date</div>
            </div>
            <!-- <button @click="calendarToggle"> {{button_val}}</button>
            <button class="request-submit-btn inactive">Submit</button>-->
          </div>
          <div v-else>
            <div class="request-date-wrapper">
              <label> Request Date <span style='color:red;'>*</span>: </label>
              <div class="input-wrapper"><input id="request-date-val" type="text" @click="calendarToggle('request','hauling')" readonly required></input><div class="calendar-icon"></div></div>
              <span class="help-text">Please select your preferable launching/hauling date, and you will be notified with our response</span>
              <!--<button @click="calendarToggle"> {{button_val}}</button> -->              
            </div>
          </div>
          <div v-if="this.date_is_shown" id="calendar-send" :class="[request_type, {arrival: calendar_date_type.arrival, request: calendar_date_type.request}]"></div>
          <button class="request-submit-btn" @click="sendRequest">Submit</button>
        </div>
        </div>
      </div>
    </transition>
  </div>`,
  data: function() {
    return {
      requestsList: [],
      isActive: {"hauling": false, "launching": false},
      send_request: false,
      flag: false,
      date_is_shown: false,
      log_requests: [],
      button_val: "Open Calendar",
      choice: undefined,
      type_is_launching: false,
      request_type: "",
      request_message: "",
      calendar_is_shown: false,
      arrival_date: "",
      request_date: "",
      calendar: "",
      calendar_arrival_date: "",
      is_calendar_init: true,
      calendar_date_type: {"arrival": false,"request": false},
      active: null
    }
  },
  watch: {
    'requestsList': function() {    
      //console.log(this.requestsList)
      if(this.requestsList[0]) {
        if(this.requestsList[0].type === 'Hauling') {
          this.isActive.launching = true;
          this.isActive.hauling = false;
        }
        else {
          this.isActive.launching = false;
          this.isActive.hauling = true; 
        }  
      } 
      else {
        if(this.data[0].field_entity_ref_cradle === '') {
          this.isActive.launching = true;
          this.isActive.hauling = false;
        }
        else {
          this.isActive.launching = false;
          this.isActive.hauling = true; 
        }        
      }
      
    },
    'send_request': function(event) {
      console.log('send_request changed');
    }
  },
  mounted: function() {
    
    if(this.data[0].field_remain_ashore === 'Yes') {
      this.active = 'yes';
    }
    else if(this.data[0].field_remain_ashore === 'No') {
      this.active = 'no';
    }
    else if(this.data[0].field_remain_ashore === 'Maybe'){
      this.active = 'maybe';
    }

    this.getUserRequests();
    this.getLogRequests();
  },
  methods: {
    getRequestType: function(){
      if(event.target.value === 'launching') {
        this.type_is_launching = true;
        this.button_val = "Open Calendar";
      }
      else {
        this.type_is_launching = false; 
      }
    },
    calendarToggle: function(type,request){
      this.calendarInit(type,request);
      this.date_is_shown = true;
      if(type === 'arrival') {
        this.calendar_date_type.arrival = true;
        this.calendar_date_type.request = false;
      }
      else {
        this.calendar_date_type.request = true;
        this.calendar_date_type.arrival = false;
      }

      // if(this.date_is_shown === false) {
      //   console.log('toggling calendar open');
      //   this.button_val = "Close Calendar";
      //   this.date_is_shown = true;

      //   //if(!this.flag) {
      //     (function($) {
      //       $(document).ready(function(){
      //         //$('#request-date-val').trigger('click');
      //       });
      //     })(jQuery); 
      //     //this.flag =true;
      //   //}
      //   this.calendarInit(type);
        
      // }else {
      //   console.log('toggling calendar closed');
      //   this.button_val = "Select Date";
      //   this.date_is_shown = false;  
      // }      
    },
    sendRequest: function() {
      console.log(this.calendar_arrival_date);
      //console.log(this.calendar.request_date);
      //console.log(calendar);
    },    
    getUserRequests: function() {
      let apiUrl = `/api/requests/user/${this.data[0].nid}?_format=json`;
      axios.get(apiUrl)
            .then(response => { this.requestsList = response.data; }) //this.nodeData = response.data
            .catch(e => { this.errors.push(e) })
    },
    sendRequestPopUp: function(request_type) {
      this.request_type = request_type;
      console.log('request type ='+request_type);
      if(this.request_type === 'launching') {
         this.type_is_launching = true;
         this.button_val = "Open Calendar";
         this.request_message = "Select the preferred dates for your request. <span class='subheader'>Click on your preferable day from the calendar to select it.</span> ";
         //this.calendarToggle('request','launching');
      }
      else {
        this.type_is_launching = false; 
        this.request_message = "Select the preferred date for your request";
        //this.calendarToggle('request','hauling');
      }
      if(!this.send_request) this.send_request = true;
      else this.send_request = false;
      
    },
    closeRequest: function() {
      this.send_request = false;
    },
    getLogRequests: function() {
      let log_req_url = '/api/list/requests/approved?_format=json'; // WAS completed+pending+
      axios.get(log_req_url)
          .then(response => { this.log_requests = response.data; })
          .catch(e => { this.errors.push(e) })
    },

    calendarInit: function(type,request) {
      //console.log(this.data);
       
       let event_counter = 0;
       let cnt = 0;
       let obj = {};
       let flag = 0;
       field_yacht_type = this.data.field_yacht_type;
       // Change values in the holidays array to update the unavailable dates to the user calendar.
       // This days must be in el-GR format. The equality check is made in the dayRender() function
       // provided by the foolcalendar (lol) plugin.
       console.log('checking date type');
       console.log(type);
       // let holidays = [];

       // if(type==='request'){
       let holidays = ['25/3/2019','26/4/2019','27/4/2019','29/4/2019','15/8/2019','16/8/2019','17/8/2019','3/8/2019','10/8/2019','24/8/2019','31/8/2019','28/10/2019','25/12/2019','26/12/2019','27/12/2019','28/12/2019']; 
       //}
       
       // approved_dates_counter is a specified counter for the events that share the same
       // requested date from the user. We use it as a flag to increment the counter for that
       // specific date. The aforementioned counter measures how many approved events there are
       // for each date.
       let approved_dates_counter = -1;
       calendar_events = this.log_requests; 
       let date_counter = [];
       if(this.is_calendar_init) {
        
        console.log('initializing calendar')
        console.log(calendar_events.length);

         for(let i=0;i<calendar_events.length;i++) {          
            if(calendar_events[i].approved_date !== '') {
              let cm_counter = 0;
              let other_counter = 0;
              let date = new Date(calendar_events[i].approved_date).toLocaleDateString('el-GR'); 
              // Get in here if the loop has just started (i===0) or anytime the previous object and the current do
              // NOT share the same approved date.
              if((i===0) || (date !== new Date(calendar_events[i-1].approved_date).toLocaleDateString('el-GR'))) {
                //console.log(calendar_events[i].approved_date);
                if(calendar_events[i].field_yacht_type === 'C/M') {
                  cm_counter += 1;
                }
                else {
                  other_counter += 1;
                }
                approved_dates_counter += 1;
                let obj = {'start': new Date(calendar_events[i].approved_date).toISOString().slice(0,10),'approved_date': new Date(calendar_events[i].approved_date).toISOString().slice(0,10),'counter':cnt, 'cm_counter': cm_counter, 'other_counter': other_counter };
                date_counter.push(obj);    
                flag = approved_dates_counter;                    
              }else {
                // in case the same date was found use the flag value from the previous step
                // to target the specific object in the date_counter array of objects.
                date_counter[flag].counter += 1;
                //date_counter[flag].cm_counter += 1;
                //date_counter[flag].other_counter += 1;
                if(calendar_events[i].field_yacht_type === 'C/M') {
                  date_counter[flag].cm_counter += 1;
                }
                else {
                  date_counter[flag].other_counter += 1;
                }
              }

            }         
        } 

        console.log('Reqiests');
        
        requests = date_counter;
        console.log(requests);
        

        // for(let i=0;i < calendar_events.length; i++) {   
                    
        //     //console.log(calendar_events[i].approved_date);
        //     //if(new Date(calendar_events[i].approved_date).toLocaleDateString('el-GR') === event_date) {
        //       let temp = {};
        //       temp.other = 0;
        //       temp.cm = 0;
        //       temp.date = new Date(calendar_events[i].approved_date).toLocaleDateString('el-GR');

        //       if(calendar_events[i].field_yacht_type === 'C/M' && calendar_events[i].status === 'Approved' && calendar_events[i].type === 'Hauling') {
        //         temp.cm += 1;
        //       }
        //       else if(calendar_events[i].status === 'Approved' && calendar_events[i].type === 'Hauling') {
        //         temp.other += 1;
        //       }
        //       requests.push(temp);
        //     //}              
        //   }

        
        this.is_calendar_init = false;
       }

       
       // // Load the JQuery plugin for the calendar functionality
       (function($) {
        $(document).ready(function(){

         let yacht_type = $('.yacht-card.type').attr('id');
         
         //console.log(requests);
         console.log(yacht_type);
         let flag = true;        
         // simulate a click event on the input as a *temp fix 
         //$('#request-date-val').once().click();

         let is_arrival_date_type = false;
         if($('#calendar-send').hasClass('arrival')){
          is_arrival_date_type = true;
         }
         // if a calendar instance was present destroy it and remake the new one.
         if( $('#calendar-send').children().length > 0 ) {
            $('#calendar-send').fullCalendar('destroy');
         }

         // Initialize the foolCalendar plugin.
         $('#calendar-send').fullCalendar({
            header: {
                left: type,
                center: 'title',
                right: 'prev,next', //month
                ignoreTimezone: false
            },
            timeFormat: 'H:mm',
            defaultView: 'month',
            month: '4',
            //events: calendar_events,
            events: requests,
            dayRender: function( date, cell ) { 
              //console.log(cell[0]);
              //console.log();
              if (((holidays.indexOf(date.toDate().toLocaleDateString('el-GR')+"") >= 0) || ($(cell[0]).hasClass('fc-sun'))) && !is_arrival_date_type){
                cell.addClass('high-dens holiday');
              }                            
            },
            eventRender: function(event, element) {
              // console.log(event);
              // console.log(element);
              // console.log('user request type')
              // console.log(yacht_type);
              //console.log('event');
              //console.log(event);
              
              if(yacht_type === 'cm') {
                yacht_type = 'cm';
              }
              else {
                yacht_type = 'other';
              }
              // Dont take into consideration availability if its arrival date
              if(!is_arrival_date_type) {
                //console.log(event.suggested_date);
                //console.log('not arrival - Checking Availability 30/4');
                
                if(event.approved_date === '3/5/2019'){
                  console.log('not arrival - Checking Availability 3/5');
                  console.log(event.approved_date);
                  console.log(checkAvailability('other','3/5/2019'));
                }                

                let is_available = checkAvailability(yacht_type,event.approved_date);
                //console.log(is_available);
                if(event.approved_date !== ''){
                  let fullcal_custom_date = new Date(event.approved_date).toISOString().slice(0,10);
                  if(is_available){
                    $('td [data-date="' +fullcal_custom_date+ '"]').css('background','#ffd07b');
                    $('td [data-date="' +fullcal_custom_date+ '"]').addClass('med-dens'); 
                  }
                  else {
                    $('td [data-date="' +fullcal_custom_date+ '"]').css('background','#ff6161');
                    $('td [data-date="' +fullcal_custom_date+ '"]').addClass('high-dens');
                  }  
                }
              }
              
              
              // console.log(is_available);
              //let fullcal_custom_date = new Date(event.approved_date).toISOString().slice(0,10);
            },
            eventClick: function(calEvent, jsEvent, view) {},
            // renderEvent: function(event) {
            //   console.log('client events hook');
            //   console.log(event);
            // },
            dayClick: function(date, jsEvent, view) {
              let sel_date = $(this).attr('data-date');  
              $('.fc-future').removeClass('selected');      

              if($('#calendar-send').hasClass('hauling')){
                if($(this).hasClass('fc-future') || $(this.hasClass('fc-today'))) {
                  if(!$(this).hasClass('high-dens')) {
                    $("#request-date-val").val(sel_date);                        
                    $(this).addClass('selected'); 
                    $('.request-submit-btn').removeClass('inactive').addClass('selectable');         
                  }
                }
              }
              else {
                if($(this).hasClass('fc-future') || $(this.hasClass('fc-today'))) {
                  if(!$(this).hasClass('high-dens')) {
                    if(type === 'arrival') {
                      $("#arrival-date-val").val(sel_date);
                      this.arrival_date = sel_date;  
                      //console.log("Request Date");
                      if($('#request-date-val').val() !== '') {
                        $('.request-submit-btn').removeClass('inactive').addClass('selectable');                    
                      }
                    }
                    else {
                      $("#request-date-val").val(sel_date);      
                      this.request_date = sel_date;   
                      if($('#arrival-date-val').val() !== '') {    
                        $('.request-submit-btn').removeClass('inactive').addClass('selectable');               
                      }
                    }                    
                    $(this).addClass('selected');                  
                  }  
                }                
              }             
            }

          });

        function checkAvailability(type, date) {
          // console.log('checking Avali. requestss');
          // console.log(requests);
          
          //console.log(type, new Date(date).toLocaleDateString('el-GR'));
          let arequests = { 'other': 0, 'cm': 0};
          let event_date = new Date(date).toLocaleDateString('el-GR');
              //console.log('Spitting approved Dates');
              //console.log('requests date = ', requests[5].date);
            for(let i=0;i<requests.length;i++) {
              // console.log(new Date(requests[i].approved_date).toLocaleDateString('el-GR'));
              // console.log(event_date);
              if(new Date(requests[i].approved_date).toLocaleDateString('el-GR') === event_date){ //approved_date
                //console.log('Events equality');
                  //console.log('equality found')
                  //console.log(type);
                //if(type === 'cm') {
                    //console.log('+= 1 cm');
                //  arequests.cm += 1;
                //}
                //else {
                    //console.log('+= 1 other');
                //  arequests.other += 1;
                //}
                  // if(type === 'other') {
                  //   arequests.other += 1;
                  // }
                  // else {
                  //   arequests.cm += 1;
                  // }
                  console.log(requests[i]);

                  if (requests[i].cm_counter == 0 && requests[i].other_counter < 10) {
                    console.log('0 is ok');
                    return true;
                  }
                  if (requests[i].cm_counter == 1 && requests[i].other_counter < 8) {
                    console.log('1 is ok');
                    return true;
                  }
                  if (requests[i].cm_counter == 2 && requests[i].other_counter < 6) {
                    console.log('2 is ok');
                    return true;
                  }
                  if (requests[i].cm_counter == 3 && requests[i].other_counter < 4) {
                    console.log('3 is ok');
                    return true;
                  }
                  console.log('not ok')
                  return false;
              }
              
                // if(event_date === '3/5/2019') {
                //   console.log(requests[i].date);
                //   console.log(event_date);
                //   console.log(arequests);
                // }
            }
          
            if(event_date === '3/5/2019') {
              //console.log(requests[i].date);
              //console.log(event_date);
              //console.log(arequests);
            }
          //}
          
          

          // if (arequests.cm == 0 && arequests.other < 10) {
          //   return true;
          // }
          // if (arequests.cm == 1 && arequests.other < 8) {
          //   return true;
          // }
          // if (arequests.cm == 2 && arequests.other < 6) {
          //   return true;
          // }
          // if (arequests.cm == 3 && arequests.other < 4) {
          //   return true;
          // }
          // return false;

        }

        if(flag){
          //enters first
          //console.log('flag if');
          //console.log(date_counter);
          initCalendarEvents(date_counter);
          flag = false;  
        }        

        $('#calendar-send button').on('click',function(){
          initCalendarEvents(date_counter);
          //this.calendar_is_shown = false;
          this.date_is_shown = false; 
          //console.log(this.date_is_shown);
        });           
        $(document).on('click','#close', function(){
          //console.log('destroying calendar');
          $('#calendar-send').fullCalendar('destroy');
          //$('#calendar-send').hide();
        });

        $('.fc-view-container').prepend(`<div class="density-notes">
          <div class="note-row"><div class="box grey"></div><span class="note-text">Past Dates (Unselectable)</span></div>
          <div class="note-row"><div class="box light-yellow"></div><span class="note-text">Today (Selectable)</span></div>
          <div class="note-row"><div class="box green"></div><span class="note-text">Available Dates</span></div>
          <div class="note-row"><div class="box orange"></div><span class="note-text">Available Dates - Limited Requests</span></div>
          <div class="note-row"><div class="box red"></div><span class="note-text">Unavailable Dates</span></div>
          <div class="note-row"><div class="box blue"></div><span class="note-text">Your Selected Date</span></div>
          </div>`)


        // executed third
        events = $('#calendar-send').fullCalendar('clientEvents');
        //console.log('client events');
        //console.log(events);


        // Prepares all events and asigns classes based on event availability for each day.
        function initCalendarEvents(date_counter) {
          // enters second
          // console.log('date counter');
          // console.log(date_counter);
          $('.fc-left').html(`<div class="date-type"> Select your <span>${type}</span> date</div>`);
          $('.fc-right').once().append('<button id="close" class="fc-state-default red">Close</button>')
          // //console.log('Init Calendar events function')
          // for(let i=0;i<date_counter.length;i++){
            

          //   let is_available = checkAvailability('other',date_counter[i].date,date_counter);
          //   let fullcal_custom_date = new Date(date_counter[i].date).toISOString().slice(0,10);
          //   if(is_available){
          //     $('td [data-date="' +fullcal_custom_date+ '"]').css('background','#ffd07b');
          //     $('td [data-date="' +fullcal_custom_date+ '"]').addClass('med-dens'); 
          //   }
          //   else {
          //     $('td [data-date="' +fullcal_custom_date+ '"]').css('background','#ff6161');
          //     $('td [data-date="' +fullcal_custom_date+ '"]').addClass('high-dens');
          //   }

          //   // --------- prev ---------
          //   // if(date_counter[i].date !== '') {
          //   //   let fullcal_custom_date = new Date(date_counter[i].date).toISOString().slice(0,10); 
          //   //   if(date_counter[i].counter >= 4) {
          //   //     $('td [data-date="' +fullcal_custom_date+ '"]').css('background','#ff6161');
          //   //     $('td [data-date="' +fullcal_custom_date+ '"]').addClass('high-dens');
          //   //   }
          //   //   else if(date_counter[i].counter >= 0){
          //   //     $('td [data-date="' +fullcal_custom_date+ '"]').css('background','#ffd07b');
          //   //     $('td [data-date="' +fullcal_custom_date+ '"]').addClass('med-dens');  
          //   //   }  
          //   // } 

          //   // -------- prev --------           
          // }  
        }

      
      // console.log(request);
      // console.log(type);

      







      $(document).once().on('click','.request-submit-btn',function(){
          

    
          if($('.request-submit-btn').hasClass('selectable')) {

            console.log('request sending');

            var options = { year: 'numeric', month: 'numeric', day: 'numeric' };
            let  request_dates = new Date($('#request-date-val').val());//.toLocaleDateString('en-US');//.replace(/['/']/g,'-');
            let arrival_dates = new Date($('#arrival-date-val').val());//.toLocaleDateString('en-US');//.replace(/['/']/g,'-');
            let yacht_id = $('#bsg-yacht').attr('data-id');            

            var req_year = request_dates.getFullYear();
            var req_month = request_dates.getMonth()+1;
            var req_day = request_dates.getDate();

            if (req_day < 10) {
              req_day = '0' + req_day;
            }
            if (req_month < 10) {
              req_month = '0' + req_month;
            }

            var arrival_year = arrival_dates.getFullYear();
            var arrival_month = arrival_dates.getMonth()+1;
            var arrival_day = arrival_dates.getDate();

            if (arrival_day < 10) {
              arrival_day = '0' + arrival_day;
            }
            if (arrival_month < 10) {
              arrival_month = '0' + arrival_month;
            }


            var request_formatted_date = req_year + '-' + req_month + '-' + req_day;
            var arrival_formatted_date = arrival_year + '-' + arrival_month + '-' + arrival_day;
            // console.log(request_formatted_date);
            // console.log(arrival_formatted_date);

            if(request === 'hauling') {
                obj = {
                "type": {
                  "value": "hauling"
                },
                "suggested_date": {
                  "value": request_formatted_date
                },
                "status": {
                  "value": "pending"
                },
                "yacht_id": {
                  "value": parseInt(yacht_id)
                }
              }  
            }
            else {
              obj = {
                "type": {
                  "value": "launching"
                },
                "arrival_date": {
                  "value": arrival_formatted_date
                },
                "suggested_date": {
                  "value": request_formatted_date
                },
                "status": {
                  "value": "pending"
                },
                "yacht_id": {
                  "value": parseInt(yacht_id)
                }
              }
            }

           
            //console.log(arrival_dates);
            // console.log(request_dates);
            // console.log(yacht_id);

            let csrf_token = "";
            var jqxhr = $.get( "/session/token", function(data) {
              csrf_token = data;
              console.log("csrf - "+csrf_token);
              $.ajax({
                type: "POST",
                headers: { 
                  'X-CSRF-Token': csrf_token, 
                  'Accept': 'application/json',
                  'Content-Type': 'application/json' 
                },
                url: "/entity/yacht_action?_format=json",  
                data: JSON.stringify(obj),
                dataType: "json"
                })
                .done(function(response) {
                  $('.send-request-message').toggle().html('<span class="success"> Your request has been succesfully sent. <br> You will soon be notified on your requests progress</span>');
                  $( "#send-request" ).scrollTop( 300 );
                  location.reload();
                }).fail(function (error) {
                    $('.send-request-message').toggle().html('<span class="fail"> Your request was not send. Please try again. </span>');
                    $( "#send-request" ).scrollTop( 300 );
                });
            })
            .fail(function() {
              console.warn( "Error getting csrf-token" );
            })
          }
          else {

            console.log('warnings');
            $('html,body').animate({scrollTop:0},0);

            if($('#arrival-date-val').val() === ''){
              $('#arrival-date-val').addClass('missing').val('Please select a date');
            }
            if($('#request-date-val').val() === '') {
              $('#request-date-val').addClass('missing').val('Please select a date'); 
            }
          }

      }); // request submit

      }) // document ready ending

      // console.log("ALL COOKIES: " + $.cookies.get());

      })(jQuery); 
    }
  }
})


var vm1 = new Vue({
  //el: '#bsg-yacht',
  router,
  data: {
    id: null,
    apiUrl: '/api/yachts/',
    nodeData: []
  },
  mounted: function() {
    this.id = this.$el.getAttribute('data-id');
    this.getNode();    
  },
  methods: {
    getNode: function(){
        this.apiUrl += this.id;
        axios.get(this.apiUrl)
            .then(response => { this.nodeData = response.data; }) //this.nodeData = response.data
            .catch(e => { this.errors.push(e) })        
    }
  }

});



// Dont Load the Yacht Instance if there is no need to.
// For example when in View Pages.
if(document.getElementById('bsg-yacht')) {
  
  vm1.$mount('#bsg-yacht');
}
