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

const Pending = Vue.component('lista', { 
  template: `
  <div>
  <h2 style="max-width:300px;">Pending Requests</h2>
  <transition name="fade" mode="out-in">
  <div class="content-wrapper pending-requests">
     <div class="pending-requests full" v-if="pendingRequests && pendingRequests[0]">
        <ul>
          <li v-for="item in pendingRequests">
            <a :href="item.edit_yacht_action"> {{ item.status }} - {{ item.yacht_id }} - {{ new Date(item.start).toLocaleDateString('el-GR') }} </a>
          </li>
        </ul>
     </div>
    </div>
  </transition>  
</div>`, 

  data: function() {
  	return {
      pendingRequests: [],
      pendingEndpoint: '/api/list/requests/pending?_format=json'
  	}
  },
  // Once mounted check if (PHP rendered) - user block exists, and if so - hide it
  // since we do not need it in these pages.
  mounted: function() {
     console.log('pending template mounted');
     let user_block = document.getElementById('block-views-block-yacht-block-1');
     let user_account = document.getElementById('block-shipyard-content');
     document.getElementById('general-container').style.display = 'none';

     if(user_block) {
     	user_block.style.display = 'none';
     }

     if(user_account) {
      user_account.style.display = 'none';
     }
    
     //this.getYachts();
     this.getPendingRequests();
  },
  // Watch for value changes in the listaData array.
  // We use it once the api results (method: getYachts) populated
  // the array, in order to remove all duplicates based on the given params.
  watch: {
    'pendingRequests': function() {
      console.log('pendingRequests changed');
      console.log(this.pendingRequests);
      //this.removeDuplicates();
    }
  },
  methods: {
    getPendingRequests: function() {
      axios.get(this.pendingEndpoint)
          .then(response => { this.pendingRequests = response.data; })
          .catch(e => { this.errors.push(e) })
    }
  },
  computed: {
  }
})
