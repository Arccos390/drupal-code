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

const Owners = { 
  template: `
<div>
    

    <h2>Owners</h2>  
    <transition name="fade" mode="out-in">
    <div class="datalist-container">
    <div class="datalist-pager">
      <span @click="getPagerData('prev')" :class="{active: this.pagerStart > 0}">Previous Page</span>
      <span @click="getPagerData('next')" class="active">Next Page</span>
    </div>
      <div class="datalist-content owners" v-if="listData && listData[0]">
          <table id="data-table" v-if="listData[0]" :class="{card: is_responsive.table}">
            <thead>
              <tr>
                <th class="lg">Name</th>
                <th>Telephone</th>
                <th>Email</th>
                <th>Yacht</th>
                <th>Position</th>
                <th class="sm">Area</th>
                <th class="sm"></th>
              </tr>  
            </thead>
            <tr v-for="item in filteredData">
              <td data-label="Name" class="lg"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_user_realname}} {{item.field_real_lastname}}</span></td>
              <td data-label="Telephone"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_user_telephone}}</span> </td>
              <td data-label="Email"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_user_email}}</span> </td>
              <td data-label="Yacht" v-html="item.title"></span></td>
              <td data-label="Position"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_yacht_position}} </span></td>
              <td data-label="Area" class="sm"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_area}}</span> </td>
              <td id="edit-user" data-label="Edit" class="sm"><a :href="item.edit_user">Edit</a></td>
            </tr>  
          </table>
      </div>
    </div>
    </transition>

    <transition name="fade" mode="out-in">
      <div>
        <div class="datalist-right-sidebar" v-if="listData && listData[0]">
          <div class="content-wrapper filters" :class="{responsivefilters: is_responsive.filters, open: is_open.filters}" >
            <h3 @click=" (is_responsive.filters == true && is_open.filters == false) ? test('filters') : (is_open.filters == is_responsive.filters == true) ? close('filters') : '' ">Filters</h3>
            <div class="filters-wrapper">
              <div class="filter" v-if="listData[0]">
                <label>NAME</label>
                <input type="text" placeholder="Type ..." class="form-control" v-model="filterValue">       
               </div>
               </div>
               
              <span class="reset-filters" @click="resetFilters">Reset Name</span>
              </div>
          
          
          <div class="content-wrapper requests" :class="{responsivefilters: is_responsive.requests, open: is_open.requests}" @click=" (is_responsive.requests == true && is_open.requests == false) ? test('requests') : (is_open.requests == is_responsive.requests == true) ? close('requests') : '' ">
            <h3>Pending Requests</h3>
            <div class="filters-wrapper">
             <div class="pending-requests" v-if="pendingRequests && pendingRequests[0]">
                <ul>
                  <li v-for="item in pendingRequests">
                    <a :href="item.edit_yacht_action">{{ item.status }} - {{ item.yacht_id }} - {{ new Date(item.start).toLocaleDateString('el-GR') }}</a>
                  </li>
                </ul>
             </div>
            <router-link id="view-requests" to="/pending-requests">See all Requests</router-link>
          </div>
         </div>
        </div>
        </div>           
        
      </transition>
      
  </div>`, 

  data: function() {
    return {
      apiUrl: '/api/list/owners?_format=json',
      listData: [],
      filterValue: '',
      pendingEndpoint: '/api/list/requests/pending?_format=json',
      pendingRequests: [],
      pagerStep: 15,
      pagerStart: 0,
      pagerEnd: 15,
      is_responsive: {'filters': false, 'requests': false, 'table': false},
      is_open: {'filters': false, 'requests': false}
    }
  },
  // Once mounted check if (PHP rendered) - user block exists, and if so - hide it
  // since we do not need it in these pages.
  mounted: function() {
     console.log('yachts template mounted');
     let user_block = document.getElementById('block-views-block-yacht-block-1');
     let user_account = document.getElementById('block-shipyard-content');
     document.getElementById('general-container').style.display = 'none';

     if(user_block) {
      user_block.style.display = 'none';
     }

     if(user_account) {
      user_account.style.display = 'none';
     }
    
     this.getOwners();
     this.getPendingRequests();
  },
  // Watch for value changes in the listaData array.
  // We use it once the api results (method: getYachts) populated
  // the array, in order to remove all duplicates based on the given params.
  watch: {
    'listData': function() {
      console.log('listData changed');

      let data = this.listData;
      console.log(data);
      for(let i=0;i<data.length;i++) {
        data[i].fullname = data[i].field_user_realname + data[i].field_real_lastname;
      }
    }
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
  methods: {
    // Make the API call and populate the listData array with the results
    getOwners: function() {
        axios.get(this.apiUrl)
            .then(response => { this.listData = response.data; })
            .catch(e => { this.errors.push(e) })
    },
    getPendingRequests: function() {
      axios.get(this.pendingEndpoint)
          .then(response => { this.pendingRequests = response.data; })
          .catch(e => { this.errors.push(e) })
    },
    resetFilters: function() {
      this.filterValue = '';
    },
    getPagerData: function(action) {
      switch(action) {
        case "next":
          this.pagerStart += this.pagerStep;
          this.pagerEnd += this.pagerStep;
          break;
        case "prev":
          this.pagerEnd -= this.pagerStep;
          this.pagerStart -= this.pagerStep;
          break;
      }
      if(this.pagerEnd < 0 || this.pagerStart < 0){
        this.pagerEnd = 15;
        this.pagerStart = 0;
      }
    },
    handleResize: function() {
      console.log(window.innerWidth);
      if(window.innerWidth <= 1600){
        this.is_responsive.filters = true;
        this.is_responsive.requests = true;
        this.is_responsive.table = false;
        if(window.innerWidth <= 730){
          this.is_responsive.table = true;
          console.log(this.filterData);
        }
      } 
      else {
        this.is_responsive.filters = false;
        this.is_responsive.table = false;
        this.is_responsive.requests = false;
      }
      console.log(this.is_responsive);
    },

    test: function(type){
      console.log('this must be in responsive mode');
      console.log(this.is_responsive);
      if(type === 'requests') {
        this.is_open.requests = true;  
      }
      else {
        this.is_open.filters = true;
      }
      
    },
    close: function(type){
      console.log('this must be in responsive mode');
      console.log(this.is_responsive);
      if(type === 'requests') {
        this.is_open.requests = false;  
      }
      else {
        this.is_open.filters = false;   
      }
      
    }
  },
  computed: {
    // Return the filtered results
    filteredData() {      
      let result = this.listData;
      let filter_results = [];
      //result = result.filter(item => item.fullname.includes(this.filterValue));
      result.filter(item => {
        if(item.fullname.toLowerCase().includes(this.filterValue.toLowerCase())){
          filter_results.push(item);
        }
      });
      
      return filter_results.slice(this.pagerStart, this.pagerEnd);
    }
  }
}
