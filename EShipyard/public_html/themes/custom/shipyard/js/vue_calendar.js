/*
  Owners component is rendered in the /yacht route and its register is on vue_datalist.js file.

  HTML: 
  - Contains a table that renders the fetched data from the Yacht endpoint, and the sidebar that
    contains the filters for the current object.

  Methods:
  - getOwners: fetches the data from the drupal endpoint and updates the local variable listData, which
    is used afterwards in our templates.

  Mounted: 
  - Checks if the Drupal rendered User block (only visible in yacht/nid) is shown - and if so it hides it.
*/
let calendar_events;
const Calendar = { 
  template: `
  <div>
  <h2>Calendar</h2>  
  <div class="calendar-actions">
    <button type="button" class="calendar-btn" :class="{active: this.is_active.arrival_dates}" @click="getRequests();prepareRequests('arrival');">Arrival Dates</button>
    <button type="button" class="calendar-btn" :class="{active: this.is_active.request_dates}" @click="getRequests();prepareRequests('request');">Request Dates</button>
  </div>
  <div class="datalist-calendar" :class="{arrival: is_active.arrival_dates, request: is_active.request_dates}">
    <div id="calendar"></div>
  </div>
  <div class="datalist-right-sidebar">
    <div class="content-wrapper requests" :class="{responsivefilters: is_responsive.requests, open: is_open.requests}" @click=" (is_responsive.requests == true && is_open.requests == false) ? test('requests') : (is_open.requests == is_responsive.requests == true) ? close('requests') : '' ">
      <h3>Pending Requests</h3>
      <div class="filters-wrapper">
       <div class="pending-requests" v-if="pendingRequests && pendingRequests[0]">
          <ul>
            <li v-for="item in pendingRequests">
              <a :href="item.edit_yacht_action"> {{ item.status }} - {{ item.yacht_id }} - {{ new Date(item.start).toLocaleDateString('el-GR') }}</a>
            </li>
          </ul>
       </div>   
       <router-link id="view-requests" to="/pending-requests">See all Requests</router-link>
      </div>
    </div>
    </div>
  </div>`, 
  data: function() {
    return {
      apiUrl: '/api/list/requests/completed+pending+approved+progress?_format=json',
      completedUrl: '/api/list/completed-requests/completed?_format=json',
      pendingEndpoint: '/api/list/requests/pending?_format=json',
      listData: [],
      cleanedData: [],
      pendingRequests: [],
      is_active: {'arrival_dates': false, 'request_dates': true },
      is_responsive: {'filters': false, 'requests': false, 'table': false},
      is_open: {'filters': false, 'requests': false},
      is_first_time: true
    }
  },
  // Once mounted check if (PHP rendered) - user block exists, and if so - hide it
  // since we do not need it in these pages.
  mounted: function() {
     console.log('calendar template mounted');
     let user_block = document.getElementById('block-views-block-yacht-block-1');
     let user_account = document.getElementById('block-shipyard-content');
     document.getElementById('general-container').style.display = 'none';

     if(user_block) {
      user_block.style.display = 'none';
     }

     if(user_account) {
      user_account.style.display = 'none';
     }
    
     this.getRequests();
     this.getPendingRequests();
  },
  
  // Add Window Resize event listener to execute code used
  // in responsive mode.
  created() {
    window.addEventListener('resize', this.handleResize)
    this.handleResize();
  },
  
  // remove window size event listener
  destroyed() {
    window.removeEventListener('resize', this.handleResize)
  },

  // Watch for value changes in the listaData array.
  // We use it once the api results (method: getYachts) populated
  // the array, in order to remove all duplicates based on the given params.
  watch: {
    'listData': function() {
      if(this.is_first_time) {
        this.prepareRequests('request');  
        this.is_first_time = false;
      }     
      
    },
    'pendingRequests': function() {
      
    }
  },
  methods: {
    // Make the API call and populate the listData array with the results. Get all the non
    // completed and also get the 100 most recent completed. Then merge these two arrays and populate
    // the list.data variable
    getRequests: function() {
      let temp = [];
      axios.get(this.apiUrl)
          .then(response => { 
            //this.listData = response.data; 
            temp = response.data;            
            axios.get(this.completedUrl)
              .then(resp => {                
                var merged = temp.concat(resp.data);                
                this.listData = merged;            
              })  
              .catch( e => { this.errors.push(e) })
          })
          .catch(e => { this.errors.push(e) })          
    },
    getPendingRequests: function() {
      axios.get(this.pendingEndpoint)
          .then(response => { this.pendingRequests = response.data; })
          .catch(e => { this.errors.push(e) })
    },
    // setPendingRequests: function() {

    // },
    prepareRequests: function(datetype) {    

      if(!this.is_first_time) {
        if(this.is_active.request_dates) {
          this.is_active.request_dates = false;
          this.is_active.arrival_dates = true;
        }
        else {
          this.is_active.request_dates = true;
          this.is_active.arrival_dates = false; 
        }  
      }  


      // datetype is the date based on the calendar will be rendered. We have two cases:
      // 1: We render the calendar based on the arrival date of the customers.
      // 2: We render the calendar based on the user's requested date (generally)
      //console.log(datetype);

      this.cleanedData = this.listData;
      
      this.cleanedData.forEach(function (item, index) {
        // Default Behavior. We just make some title alterations and the "start" aliased date
        // is set as the event date. In this case start = suggested_date. 
        if(datetype === 'request') {
          //console.log(item.field_has_paid);
          if(item.field_has_paid === 'True') {
            item.title = 'Paid | ';
          }
          else {
            item.title = 'Not Paid | ';
          }

          if(item.type !== 'Launching') {
           item.title += `${item.type} : ${item.yacht_id}`;  
          }
          else {
            //console.log(item);
            let arrival_date = "";
            if(item.arrival_date !== ''){
              arrival_date = '-' + new Date(item.arrival_date).toLocaleDateString('el-GR');
            }
            item.title += `${item.type} :  ${item.yacht_id} - ${item.field_entity_ref_cradle} - ${item.field_area} ${arrival_date}`;   
          }  
        }
        // Set start = arrival_date to switch calendar rendering.
        else {
          if(item.field_has_paid === 'True') {
            item.item = 'Paid | ';
          }
          else {
            item.title = 'Not Paid | ';
          }
          //console.log('now rendering arrivals');
          if(item.type === 'Launching') {
            item.title += `${item.type} :  ${item.yacht_id} - ${item.field_entity_ref_cradle} - ${item.field_area}`;   
            item.start = item.arrival_date;
            item.node_url = `yacht/${item.yacht_id.toLowerCase()}`;
            //console.log(item.start);

          }
          else {
            item.start = "";
           // console.log(this.cleanedData[index]);
          }
        }       

        item.className = item.status.toLowerCase();
        //item.url = `yacht/${item.yacht_id.toLowerCase()}`;
        item.url = item.edit_yacht_action;
          
      });

      // console.log(this.cleanedData);
      // console.log(this.listData);

      this.calendarInit();
    },
    // Calendar Init receives an object that contains the data required to be rendered.
    // Some of these are custom made in the PrepareRequests() function. As far as the event date
    // is concerned ** IMPORTANT ** this is by default set with the "start" alias in the drupal view.
    // FoolCalendar automatically renders the "start" labeled field in the object as the event's date.
    // Therefore, if we need to switch to "arrivals's dates" as our events - we just need to set the
    // the listData object's start variable to the one that is the user's arrival.
    calendarInit: function() {
      let event_counter = 0;
      calendar_events = this.cleanedData;
      // console.log('calendar init');
      //console.log(calendar_events);
      
      // // Load the JQuery plugin for the calendar functionality
       (function($) {
        $(document).ready(function(){

          let cnt = -1;
          //console.log(calendar_events);

          if( $('#calendar').children().length > 0 ) {
              $('#calendar').fullCalendar('destroy');
              //console.log('destroyed calendar');
           }

        $('#calendar').fullCalendar({
          header: {
              left: 'prev,next today',
              center: 'title',
              right: 'basicWeek,basicDay,listMonth', //month
              ignoreTimezone: false
          },
          timeFormat: 'H:mm',
          defaultView: 'basicDay',
          titleFormat: 'DD MMM, YYYY',
          columnHeaderFormat: 'ddd DD MMM',
          listDayFormat: 'D MMMM',
          //defaultView: 'basicWeek',
          events: calendar_events,
          eventRender: function (event, element, view) {       
            //cnt is used to init the looping throught the calendar_events array.             
            //console.log("ELEM ", element[0])
            //console.log(element);
            element.each(function (item, index) {

              cnt++;
              //console.log(item);
              //console.log(calendar_events[cnt].edit_yacht_action);
              //console.log(item);
              let list_item = "";
              let month = "";
              if(view.name === 'listMonth') {
                list_item = $(this).closest('.fc-list-item');  
                month = true;
              }
              else if(view.name === 'basicDay') {
                list_item = $(this);
              }
              else if(view.name === 'basicWeek') {
                list_item = $(this);
              }
              
              var paid_status = "";
              
              if($(this).find('.fc-title').length){
                if($(this).find('.fc-title')[0].textContent.startsWith("Paid")){
                  paid_status = "<span class='admin-paid has-paid'></span>";
                }
                else {
                  paid_status = "<span class='admin-paid has-not-paid'></span>";
                }
              }

              if($(this).find('.fc-list-item-title a, .fc-day-grid-event .fc-title').text().startsWith('Paid')) {                
                $(this).find('.fc-list-item-marker .fc-event-dot').css('background-color', 'green');
              }
              else {                
                $(this).find('.fc-list-item-marker .fc-event-dot').css('background-color', 'red');
              }
              $(this).find('.fc-title').prepend(paid_status);
                            

              if(list_item.hasClass('pending')) {
                
                if(month) {
                  $(this).find('.fc-list-item-title').append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> Pending </span>`);
                } else {
                  $(this).append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> Pending </span>`);   
                }               
              }
              else if(list_item.hasClass('completed')) {
                
                if(month) {
                  $(this).find('.fc-list-item-title').append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> Completed </span>`);
                }else {
                  $(this).append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> Completed </span>`);  
                }                
              }
               else if(list_item.hasClass('progress')) {
                
                if(month) {
                  $(this).find('.fc-list-item-title').append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> In progress </span>`);
                } else {
                  $(this).append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> In progress </span>`);
                }
                
              }
              else if(list_item.hasClass('approved')) {
                
                if(month) {
                  $(this).find('.fc-list-item-title').append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> Approved </span>`);//${calendar_events[cnt].edit_yacht_action}
                } else {
                  $(this).append(`<a class="edit-action" href="#">Edit</a><span class="calendar-status"> Approved </span>`);
                }
                
              }
              
            });
          },
          eventClick: function(calEvent, jsEvent, view) {
            $(window).href(calEvent.edit_yacht_action);
          }
        });
       

        })

      })(jQuery);
      
    },
    handleResize: function() {
      //console.log(window.innerWidth);
      if(window.innerWidth <= 1600){
        this.is_responsive.filters = true;
        this.is_responsive.requests = true;
        this.is_responsive.table = false;
        if(window.innerWidth <= 730){
          this.is_responsive.table = true;
          //console.log(this.filterData);
        }
      } 
      else {
        this.is_responsive.filters = false;
        this.is_responsive.table = false;
        this.is_responsive.requests = false;
      }
      //console.log(this.is_responsive);
    },
    test: function(type){
      // console.log('this must be in responsive mode');
      // console.log(this.is_responsive);
      if(type === 'requests') {
        this.is_open.requests = true;  
      }
      else {
        this.is_open.filters = true;
      }
      
    },
    close: function(type){
      // console.log('this must be in responsive mode');
      // console.log(this.is_responsive);
      if(type === 'requests') {
        this.is_open.requests = false;  
      }
      else {
        this.is_open.filters = false;   
      }
      
    }
  }
}
