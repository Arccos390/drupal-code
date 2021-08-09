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

const Cradles = { 
  template: `
  <div>
  <h2>Cradles</h2> 
  <transition name="fade" mode="out-in">
  <div class="datalist-container">
  <div v-if="this.is_loading">
    <img style="width: 350px;margin: 0 auto;padding-top:30px;" src="/themes/custom/shipyard/images/loading.gif"/>
  </div>
  <div class="datalist-pager" v-if="!is_loading">
      <span @click="getPagerData('prev')" :class="{active: this.pagerStart > 0}">Previous Page</span>
      <span @click="getPagerData('next')" class="active">Next Page</span>
    </div>
    <div class="datalist-content cradles" v-if="listData && listData[0]">
        <table id="data-table" v-if="listData[0]" :class="{card: is_responsive.table}">
          <thead>
            <tr>
              <th class="xs">No</th>
              <th class="sm">Type</th>
              <th>Dimensions</th>
              <th class="sm">Plus</th>
              <th>Status</th>
              <th class="sm">Area</th>
              <th>Yacht</th>
              <th>Name</th>
              <th></th>
            </tr>  
          </thead>
          <tr v-for="(item,index) in filteredData">
            <td data-label="No" class="xs"> <span :class="[{'bt-content': is_responsive.table}]">{{ item.no }}</span> </td>
            <td data-label="Type" class="sm"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_cradle_type}}</span> </td>
            <td data-label="Dimensions"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_cradle_dimensions}} </span></td>
            <td data-label="Plus" class="sm"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_cradle_plus}} </span></td>
            <td data-label="Status"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_cradle_status}} </span></td>
            <td data-label="Area" class="sm"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_area}} </span></td>
            <td data-label="Yacht"> <span :class="[{'bt-content': is_responsive.table}]">{{item.field_cradle_yacht}} </span></td>
            <td data-label="Name"> <span :class="[{'bt-content': is_responsive.table}]">{{item.title}}</span></td>
            <td data-label="Edit" class="edit sm"><a :href="item.edit_node">Edit</a></td>
          </tr>  
        </table>
      
    </div>
    </div>
  </transition>
  <!-- Right Sidebar -->
    <!-- Right Sidebar -->
      <transition name="fade" mode="out-in">
        <div>
        <div class="datalist-right-sidebar" v-if="listData && listData[0]">
          <!-- <div class="content-wrapper filters" :class="{responsivefilters: is_responsive.filters, open: is_open.filters}" @click=" (is_responsive.filters == true && is_open.filters == false) ? test('filters') : (is_open.filters == is_responsive.filters == true) ? close('filters') : '' "> -->
          <div class="content-wrapper filters" :class="{responsivefilters: is_responsive.filters, open: is_open.filters}" >
          <h3 @click=" (is_responsive.filters == true && is_open.filters == false) ? test('filters') : (is_open.filters == is_responsive.filters == true) ? close('filters') : '' ">Filters</h3>
           <div class="filters-wrapper">
            <div class="filter filter-type">
              <label>CRADLE No</label>
              <input type="text" placeholder="Type ..." class="form-control" v-model="cradleNameValue">
           </div>
           <div class="checkbox-container filter-type">
              <label>TYPE</label>
              <div :class="['checkbox-wrapper', {'md-col': !is_responsive.filters, 'sm-col': is_responsive.filters }]" v-for="input in type.cleaned">
              <!--<div class="checkbox-wrapper md-col" v-for="input in type.cleaned">-->
                <label>
                  <input type="checkbox" :id="input.field_cradle_type" :value="input.field_cradle_type" v-model="type.checked">
                  <span class="checkbox-target"></span> 
                  <span class="filter-label">{{ input.field_cradle_type }}</span>
                </label>
              </div>
           </div>

           <div class="checkbox-container filter-type">
              <label>DIMENSIONS</label>
              <div :class="['checkbox-wrapper', {'md-col': !is_responsive.filters, 'lg-col': is_responsive.filters }]" v-for="input in dimensions.cleaned">
              <!--<div class="checkbox-wrapper md-col" v-for="input in dimensions.cleaned">-->
                <label>
                  <input type="checkbox" :id="input.field_cradle_dimensions" :value="input.field_cradle_dimensions" v-model="dimensions.checked">
                   <span class="checkbox-target"></span> 
                  <span class="filter-label">{{ input.field_cradle_dimensions }} </span>
                </label>
              </div>
           </div>

           <div class="checkbox-container filter-type">
              <label>STATUS</label>
              <div :class="['checkbox-wrapper', {'lg-col': !is_responsive.filters, 'md-col': is_responsive.filters }]" v-for="input in status.cleaned">
              <!--<div class="checkbox-wrapper lg-col" v-for="input in status.cleaned">-->
                <label>
                  <input type="checkbox" :id="input.field_cradle_status" :value="input.field_cradle_status" v-model="status.checked">
                  <span class="checkbox-target"></span> 
                  <span class="filter-label">{{ input.field_cradle_status }} </span>
                </label>
              </div>
           </div>

           <div class="checkbox-container filter-type">
              <label>AREA</label>
              <div :class="['checkbox-wrapper', {'sm-col': !is_responsive.filters, 'md-col': is_responsive.filters }]" v-for="input in area.cleaned">
              <!--<div class="checkbox-wrapper sm-col" v-for="input in area.cleaned">-->
                <label>
                  <input type="checkbox" :id="input.field_area" :value="input.field_area" v-model="area.checked">
                  <span class="checkbox-target"></span>
                  <span class="filter-label">{{ input.field_area }} </span>
                </label>
              </div>
           </div>

           <div class="checkbox-container filter-type">
              <label>PLUS</label>
              <div class="checkbox-wrapper sm-col" v-for="input in plus.cleaned">
                <label>
                  <input type="checkbox" :id="input.field_cradle_plus" :value="input.field_cradle_plus" v-model="plus.checked">
                  <span class="checkbox-target"></span>
                  <span class="filter-label">{{ input.field_cradle_plus }} </span>
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
  		apiUrl: '/api/list/cradles?_format=json',
  		listData: [],
      filterValue: '',
      cradleNameValue: '',
      area: {"checked": [], "cleaned": []},
      type: {"checked": [], "cleaned": []},
      dimensions: {"checked": [], "cleaned": []},
      status: {"checked":[], "cleaned": []},
      plus: {"checked":[], "cleaned": []},
      show: false,
      pendingEndpoint: '/api/list/requests/pending?_format=json',
      pendingRequests: [],
      pagerStep: 15,
      pagerStart: 0,
      pagerEnd: 15,
      is_responsive: {'filters': false, 'requests': false, 'table': false},
      is_open: {'filters': false, 'requests': false},
      is_loading: true
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
  // Once mounted check if (PHP rendered) - user block exists, and if so - hide it.
  mounted: function() {
   console.log('cradles template mounted');
   let user_block = document.getElementById('block-views-block-yacht-block-1');
   let user_account = document.getElementById('block-shipyard-content');
   document.getElementById('general-container').style.display = 'none';

   if(user_block) {
    user_block.style.display = 'none';
   }

   if(user_account) {
    user_account.style.display = 'none';
   }
   this.getCradles();
   this.getPendingRequests();
   
  },
  // Watch for value changes in the listaData array.
  // We use it once the api results (method: getYachts) populated
  // the array, in order to remove all duplicates based on the given params.
  watch: {
    'listData': function() {
      //console.log('listData changed');
      this.removeDuplicates();
      //console.log(this.listData);
      this.is_loading = false; 
      this.cleanNo();
    }
  },
  methods: {
    cleanNo: function(){ //index str
      for(let i=0;i< this.listData.length;i++){
        this.listData[i].no = this.listData[i].title.match(/^(\d+)/)[0];  
        //this.listData[i].no = "no";
      }
      
      //return str.match(/^(\d+)/)[0];
      // if(index === this.listData.length) {
      //   this.listData.sort((a, b) => a.title.match(/^(\d+)/)[0].localeCompare(b.title.match(/^(\d+)/)[0]));    
      // }
    },
  	getCradles: function() {  		
        axios.get(this.apiUrl)
            .then(response => { this.listData = response.data; }) // this.listData.sort((a, b) => a.parseInt(title.match(/^(\d+)/)[0]).localeCompare(b.parseInt(title.match(/^(\d+)/)[0])));    
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
      // this.listData.sort((a, b) => a.title.match(/^(\d+)/)[0].localeCompare(b.title.match(/^(\d+)/)[0]));
      // console.log(this.listData);
      things.thing = this.listData;
      
      this.area.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_area === thing.field_area && t.field_area === thing.field_area
        ))
      )
      this.type.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_cradle_type === thing.field_cradle_type && t.field_cradle_type === thing.field_cradle_type
        ))
      )   
      this.dimensions.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_cradle_dimensions === thing.field_cradle_dimensions && t.field_cradle_dimensions === thing.field_cradle_dimensions
        ))
      )

      // for(let i=0;i<this.dimensions.cleaned.length;i++) {
      //   if(this.dimensions.cleaned[i].length === 6){
      //     this.dimensions.cleaned[i].field_cradle_dimensions = "0" + this.dimensions.cleaned[i].field_cradle_dimensions;
      //   }
      //   console.log(this.dimensions.cleaned[i].field_cradle_dimensions);
      // }

      this.dimensions.cleaned.sort((a, b) => a.field_cradle_dimensions.replace('X','').localeCompare(b.field_cradle_dimensions.replace('X','')));
      //.sort();

      this.status.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_cradle_status === thing.field_cradle_status && t.field_cradle_status === thing.field_cradle_status
        ))
      ) 

      this.plus.cleaned = things.thing.filter((thing, index, self) =>
        index === self.findIndex((t) => (
          t.field_cradle_plus === thing.field_cradle_plus && t.field_cradle_plus === thing.field_cradle_plus
        ))
      ) 
    },
    resetFilters: function() {
      this.area.checked = [];
      this.type.checked = [];
      this.dimensions.checked = [];
      this.status.checked = [];
      this.plus.checked = [];
      //console.log(this.listData);
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
     // console.log(this.is_responsive);
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
    //  console.log('this must be in responsive mode');
    //  console.log(this.is_responsive);
      if(type === 'requests') {
        this.is_open.requests = false;  
      }
      else {
        this.is_open.filters = false;   
      }
      
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
          case "no":
            filter_field = item.no;
            is_name_search = true;
            break;
          case "area":
            filter_field = item.field_area;
            break;  
          case "status":
            filter_field = item.field_cradle_status;
            break;  
          case "dimensions":
            filter_field = item.field_cradle_dimensions;
            break;
          case "plus":
            filter_field = item.field_cradle_plus;
            break;
          case "type":
            filter_field = item.field_cradle_type;
            break;  
        }    
        // If the user searches based on name, instead of looking for equalities we check
        // whether the item.title includes tha given letters.
        if(is_name_search) {
          
          // if(filter_field.toLowerCase().includes(filters_specified.toLowerCase())){
          //   temp.push(item);
          // }
          //console.log(filter_field)
          if(filter_field != undefined && filter_field.includes(filters_specified)) {
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
      //console.log(action);
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
    }
  },
  computed: {
    // Return the filtered results
    // filteredData() {      
    //   let result = this.listData;
    //   //result = result.filter(item => item.title.includes(this.filterValue));
      
    //   console.log(this.area.checked);
    //   result = result.filter(item => item.field_area.includes(this.area.checked));
    //   //result = result.filter(item => item.field_yacht_position.includes(this.checkedPositions));
    //   result = result.filter(item => item.field_cradle_type.includes(this.type.checked));
    //   result = result.filter(item => item.field_cradle_dimensions.includes(this.dimensions.checked));
    //   result = result.filter(item => item.field_cradle_status.includes(this.status.checked));
    //   result = result.filter(item => item.field_cradle_plus.includes(this.plus.checked));
    //   return result;
    // }
    // Return the filtered results
    filteredData() {      
      let api_data = this.listData;
      let filter_results = [];
      let filtered = false;
      //console.log(api_data);
      
      // Depending on which filters have been defined by the user execute each filtering
      // procedures. If no filters are specified - switch to the default data received.
      if(this.cradleNameValue !== '') {
        filter_results = this.filterData(api_data,filter_results,'no',this.cradleNameValue);
        filtered = true;
      }
      if(this.area.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'area',this.area.checked);
        filtered = true;
      }
      if(this.type.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'type',this.type.checked);
        filtered = true;
      }
      if(this.dimensions.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'dimensions',this.dimensions.checked);
        filtered = true;
      }
      if(this.status.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'status',this.status.checked);
        filtered = true;
      }
      if(this.plus.checked.length) {
        filter_results = this.filterData(api_data,filter_results,'plus',this.plus.checked);
        filtered = true;
      }
      // If no filter is specified - show all data.
      if(!filtered){
        filter_results = api_data;
      }
      
      return filter_results.slice(this.pagerStart, this.pagerEnd);
    }

  }
}
