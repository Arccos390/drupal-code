/*
	Yachts component is rendered in the /yacht route and its register is on vue_datalist.js file.

	HTML: 
	- Contains a table that renders the fetched data from the Yacht endpoint, and the sidebar that
	  contains the filters for the current object.

	Methods:
	- getYachts: fetches the data from the drupal endpoint and updates the local variable listData, which
	  is used afterwards in our templates.

	Mounted: 
	- Checks if the Drupal rendered User block (only visible in yacht/nid) is shown - and if so it hides it.
*/

const Yachts = Vue.component('list', { 
  template: `
  <div>
  <h2>Yachts</h2>
  <transition name="fade" mode="out-in">
  <div class="datalist-container" v-if="listData && listData[0]">
    <div class="datalist-pager">
      <span @click="getPagerData('prev')" :class="{active: this.pagerStart > 0}">Previous Page</span>
      <span @click="getPagerData('next')" class="active">Next Page</span>
    </div>
    <div class="datalist-content yachts">
        <table id="data-table" v-if="listData[0]" :class="{card: is_responsive.table}">
          <thead>
            <tr>
              <th class="sm">Paid</th>
              <th class="lg">Name</th>
              <th class="sm">LOA</th>
              <th class="sm">Type</th>
              <th class="sm">Weight</th>
              <th class="sm">Draft</th>
              <th>Flag</th>
              <th>Position</th>
              <th>Area</th>
              <th>Cradle</th>
              <th></th>
            </tr>  
          </thead>
          <tbody>
          <tr v-for="item in filteredData">
            <td data-label="Name" class="xs">
              <span v-if="item.field_has_paid === '1'" class="has-paid"></span>
              <span v-else class="has-not-paid"></span>
            </td>
            <td data-label="Name" class="lg" v-html="item.title"></td>
            <td data-label="LOA" class="xs"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_yacht_loa}}</span></td>
            <td data-label="Type" class="xs"> 
              <span v-if="(item.field_yacht_type_1.toLowerCase().replace('/','') === 'cm') || (item.field_yacht_type_1.toLowerCase().replace('/','') === 'mb') || (item.field_yacht_type_1.toLowerCase().replace('/','') === 'sy')">
                <span v-if="!is_responsive.table" :class="[item.field_yacht_type_1.toLowerCase().replace('/','')]"></span>
                <span v-if="is_responsive.table" :class="[{'bt-content': is_responsive.table}]">
                  <span :class="[item.field_yacht_type_1.toLowerCase().replace('/','')]"></span>
                </span>
              </span>
              <span v-else> 
                <span v-if="!is_responsive.table" :class="[item.field_yacht_type_1.toLowerCase().replace('/','')]">{{ item.field_yacht_type_1 }}</span>
                <span v-if="is_responsive.table" :class="[{'bt-content': is_responsive.table}]">
                  <span :class="[item.field_yacht_type_1.toLowerCase().replace('/','')]"> {{ item.field_yacht_type_1 }} </span>
                </span>
              </span>

            </td>
            <td data-label="Weight" class="xs"> <span :class="[{'bt-content': is_responsive.table}]"> {{item.field_yacht_weight}} </span></td>
  					<td data-label="Draft" class="xs"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_yacht_draft}}</span> </td>
  					<td data-label="Flag" class="xs">
              <span v-if="!is_responsive.table" :class="['flag', item.field_yacht_nationality.toLowerCase()]"></span>
              <span v-if="is_responsive.table" :class="[{'bt-content': is_responsive.table}]">
                <span :class="['flag', item.field_yacht_nationality.toLowerCase()]"></span>
              </span>            
            </td>
  					<td data-label="Position"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_yacht_position}} </span></td>
  					<td data-label="Area"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_area}}</span> </td>
  					<td data-label="Cradle"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_entity_ref_cradle}}</span></td>
            <td data-label="Edit" class="edit sm"><a :href="item.edit_node">Edit</a></td>
          </tr>  
          </tbody>
        </table>
    </div>
  </div>
  </transition>
  <!-- Right Sidebar Area -->
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
     <div class="checkbox-container filter-type">
     	<label>AREA</label>
     	<div :class="['checkbox-wrapper', {'md-col': !is_responsive.filters, 'lg-col': is_responsive.filters }]" v-for="input in area.cleaned">
     		<label>
          <input type="checkbox" :id="input.field_area" :value="input.field_area" v-model="area.checked">
          <span class="checkbox-target"></span> 
         <span class="filter-label">{{ input.field_area }} </span>
        </label>
     	</div>
     </div>
     <div class="checkbox-container filter-position">
     	<label>POSITION</label>
     	<div class="checkbox-wrapper lg-col" v-for="input in position.cleaned">
     		<label>
          <input type="checkbox" :id="input.field_yacht_position" :value="input.field_yacht_position" v-model="position.checked">
          <span class="checkbox-target"></span> 
          <span class="filter-label">{{ input.field_yacht_position }} </span>
        </label>
      </div>
     </div>
     <div class="checkbox-container filter-type">
      <label>TYPE</label>
      <div class="checkbox-wrapper md-col" v-for="input in type.cleaned">
        <label>
          <input type="checkbox" :id="input.field_yacht_type_1" :value="input.field_yacht_type_1" v-model="type.checked">
          <span class="checkbox-target"></span> 
          <span class="filter-label">{{ input.field_yacht_type_1 }} </span>
        </label>
      </div>
     </div>
     <div class="checkbox-container filter-maintenance">
      <label>MAINTENANCE</label>
      <div class="checkbox-wrapper sm-col" v-for="input in maintenance.cleaned">
        <label>
          <input type="checkbox" :id="input.field_maintenance" :value="input.field_maintenance" v-model="maintenance.checked">
          <span class="checkbox-target"></span> 
          <span class="filter-label">{{ input.field_maintenance }}</span>
        </label>
      </div>
     </div>
     </div>

    <span class="reset-filters" @click="resetFilters">Reset all</span>
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
  		apiUrl: '/api/list/yachts?_format=json',
  		listData: [],
  		filterValue: '',
  		checkedAreas: [],
      areaSingle: [],
      checkedPositions: [],
      positionSingle: [],
      checkedType: [],
      typeSingle: [],
      area: {"checked": [], "cleaned": []},
      position: {"checked": [], "cleaned": []},
      type: {"checked": [], "cleaned": []},
      maintenance: {"checked": [], "cleaned": []},
      pendingRequests: [],
      pendingEndpoint: '/api/list/requests/pending?_format=json',
      pagerStep: 15,
      pagerStart: 0,
      pagerEnd: 15,
      is_responsive: {'filters': false, 'requests': false, 'table': false},
      is_open: {'filters': false, 'requests': false}
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
    
     this.getYachts();
     this.getPendingRequests();
  },

  // Watch for value changes in the listaData array.
  // We use it once the api results (method: getYachts) populated
  // the array, in order to remove all duplicates based on the given params.
  watch: {
    'listData': function() {
      console.log('listData changed');
      console.log(this.listData);
      this.removeDuplicates();
    }
  },

  methods: {
    // Make the API call and populate the listData array with the results
  	getYachts: function() {  		
        axios.get(this.apiUrl)
            .then(response => { this.listData = response.data; })
            .catch(e => { this.errors.push(e) })
  	},

    getPendingRequests: function() {
      axios.get(this.pendingEndpoint)
          .then(response => { this.pendingRequests = response.data; })
          .catch(e => { this.errors.push(e) })
    },

    // ** TODO: needs optimization **
    // removeDuplicates is executed in order to filter the listData array to single items based on 
    // given fields. 
    removeDuplicates() {      
      things = new Object();
      things.thing = this.listData;
      
      this.area.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_area === thing.field_area && t.field_area === thing.field_area
        ))
      )
      this.position.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_yacht_position === thing.field_yacht_position && t.field_yacht_position === thing.field_yacht_position
        ))
      )
      this.type.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_yacht_type_1 === thing.field_yacht_type_1 && t.field_yacht_type_1 === thing.field_yacht_type_1
        ))
      )
      this.maintenance.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_maintenance === thing.field_maintenance && t.field_maintenance === thing.field_maintenance
        ))
      )      
    },

    resetFilters: function() {
      this.area.checked = [];
      this.type.checked = [];
      this.position.checked = [];
      this.maintenance.checked = [];
      this.filterValue = '';
    },

    /* FilterData is the main function that provides the filtering functionality 
       @Receives: 
       - api_data -> full data from yacht endpoint with no filtering applied
       - filtered_data -> the filtered data array from each filtering event. This is 
       crucial in cases that chained filtering is required. F.E. if Area='A' filter is specified
       and later Position="Sail" filter is added, we need to filter based on the previous filtered data
       array.
       - filter_type -> A string that contains the type of the filtering to be applied
       - filters_specified -> An array of strings that contains the filters specified by the user.
       
       @Returns: An array of objects with the filtered results.
    */
    filterData: function(api_data,filtered_data,filter_type,filters_specified) {
     let temp = [];
     let data = [];
     let is_name_search = false;

     if(filtered_data.length) {
      data = filtered_data;   
     }else {
      data = api_data;
     }

     data.filter(item => {

        switch(filter_type){
          case "name":
            filter_field = item.title;
            is_name_search = true;
            break;
          case "area":
            filter_field = item.field_area;
            break;  
          case "position":
            filter_field = item.field_yacht_position;
            break;  
          case "type":
            filter_field = item.field_yacht_type_1; 
            break;  
          case "maintenance":
            filter_field = item.field_maintenance;
            break; 
        }    
        // If the user searches based on name, instead of looking for equalities we check
        // whether the item.title includes tha given letters.
        if(is_name_search) {
          if(filter_field.toLowerCase().includes(filters_specified.toLowerCase())){
            temp.push(item);
          }
        }else {
          for(let k=0; k < filters_specified.length;k++) {
            if(filter_field.toLowerCase() === filters_specified[k].toLowerCase()){
              temp.push(item);
            }
          }  
        }
      
      });

      return temp; 
    },

    getPagerData: function(action) {
      console.log(action);
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
      
    },
    filteredData() {      
      let api_data = this.listData;
      let filter_results = [];
      let filtered = false;
      
      
      // Depending on which filters have been defined by the user execute each filtering
      // procedures. If no filters are specified - switch to the default data received.
      if(this.filterValue !== '') {
        filter_results = this.filterData(api_data,filter_results,'name',this.filterValue);
        filtered = true;
      }
      if(this.area.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'area',this.area.checked);
        filtered = true;
      }
      if(this.position.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'position',this.position.checked);
        filtered = true;
      }
      if(this.type.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'type',this.type.checked);
        filtered = true;
      }
      if(this.maintenance.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'maintenance',this.maintenance.checked);
        filtered = true;
      }
      // If no filter is specified - show all data.
      if(!filtered){
        filter_results = api_data;
      }
      
      return filter_results.slice(this.pagerStart, this.pagerEnd);
    }
  },
  computed: {
    // Return the filtered results
    filteredData() {      
      let api_data = this.listData;
      let filter_results = [];
      let filtered = false;
      
      
      // Depending on which filters have been defined by the user execute each filtering
      // procedures. If no filters are specified - switch to the default data received.
      if(this.filterValue !== '') {
        filter_results = this.filterData(api_data,filter_results,'name',this.filterValue);
        filtered = true;
      }
      if(this.area.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'area',this.area.checked);
        filtered = true;
      }
      if(this.position.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'position',this.position.checked);
        filtered = true;
      }
      if(this.type.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'type',this.type.checked);
        filtered = true;
      }
      if(this.maintenance.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'maintenance',this.maintenance.checked);
        filtered = true;
      }
      // If no filter is specified - show all data.
      if(!filtered){
        filter_results = api_data;
      }

      console.log(filter_results);
      
      return filter_results.slice(this.pagerStart, this.pagerEnd);
    }
  }
})
