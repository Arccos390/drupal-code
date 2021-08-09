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

const Areas = Vue.component('shipyardAreas', { 
  template: `
  <div>
  <h2 style="max-width:300px;">Shipyard Areas</h2>
  <transition name="fade" mode="out-in">
    <div class="content-wrapper">
      
      <iframe src="https://www.google.com/maps/d/embed?mid=1tqB0KS-Ku14vRMntyHU8qabvz_89vp3B&hl=el" width="100%" height="480" align="center"></iframe>   

    </div>
  </transition>  
</div>`, 

  data: function() {
  	return {
      pendingRequests: [],
      
  	}
  },
  // Once mounted check if (PHP rendered) - user block exists, and if so - hide it
  // since we do not need it in these pages.
  mounted: function() {
     console.log('shipyard areas template mounted');
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
     // this.getPendingRequests();
  }
})
