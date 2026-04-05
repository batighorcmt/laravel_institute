<template>
  <div class="card card-outline card-primary shadow-sm border-0 rounded-lg">
    <div class="card-header bg-white border-bottom-0 pb-0 pt-4">
      <h3 class="card-title text-xl font-semibold text-gray-800">
        <i class="fas fa-desktop text-indigo-500 mr-2"></i> Website Configuration
      </h3>
    </div>
    <div class="card-body">
      <div class="alert alert-info bg-indigo-50 text-indigo-800 border-indigo-200">
        <i class="fas fa-info-circle mr-2"></i> Here you will be able to customize your school's public website.
      </div>
      
      <div class="row mt-4">
        <div class="col-md-6 mb-4">
           <div class="p-4 border rounded-lg bg-gray-50 flex items-start space-x-4">
             <div class="bg-indigo-100 text-indigo-600 p-3 rounded-full shrink-0">
               <i class="fas fa-paint-brush text-xl"></i>
             </div>
             <div>
               <h4 class="font-semibold text-gray-800 mb-1">Theme Colors</h4>
               <p class="text-sm text-gray-600 mb-3">Customize the primary colors used on your homepage.</p>
               <button class="btn btn-sm btn-primary bg-indigo-600 hover:bg-indigo-700 border-0" disabled>Coming Soon</button>
             </div>
           </div>
        </div>
        
        <div class="col-md-6 mb-4">
           <div class="p-4 border rounded-lg bg-gray-50 flex items-start space-x-4">
             <div class="bg-green-100 text-green-600 p-3 rounded-full shrink-0">
               <i class="fas fa-images text-xl"></i>
             </div>
             <div>
               <h4 class="font-semibold text-gray-800 mb-1">Hero Section</h4>
               <p class="text-sm text-gray-600 mb-3">Update your welcome message and hero background image.</p>
               <button class="btn btn-sm btn-primary bg-indigo-600 hover:bg-indigo-700 border-0" disabled>Coming Soon</button>
             </div>
           </div>
        </div>
        
        <div class="col-md-6 mb-4">
           <div class="p-4 border rounded-lg bg-gray-50 flex items-start space-x-4">
             <div class="bg-orange-100 text-orange-600 p-3 rounded-full shrink-0">
               <i class="fas fa-bullhorn text-xl"></i>
             </div>
             <div>
               <h4 class="font-semibold text-gray-800 mb-1">Notice Board Sync</h4>
               <p class="text-sm text-gray-600 mb-3">Choose which notices appear on the public website.</p>
               <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="customSwitch1" checked>
                  <label class="custom-control-label cursor-pointer text-sm font-medium" for="customSwitch1">Sync Active</label>
               </div>
             </div>
           </div>
        </div>
        
        <div class="col-md-6 mb-4">
           <div class="p-4 border rounded-lg bg-gray-50 flex flex-col justify-between h-full">
             <div>
                <h4 class="font-semibold text-gray-800 mb-2">Site Status</h4>
                <p class="text-sm text-gray-600 mb-3" v-if="schoolDomain">Currently, your website is active and accessible at <a :href="'http://' + schoolDomain + ':8000'" target="_blank" class="text-indigo-600 underline font-medium">{{ schoolDomain }}</a>.</p>
                <p class="text-sm text-gray-600 mb-3" v-else>No domain has been assigned to this school yet. Please contact the super admin.</p>
             </div>
             <div>
                <a :href="'/admission/' + schoolCode" target="_blank" class="btn btn-outline-secondary btn-sm mr-2">
                   View Admission Page <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                </a>
                <a href="/" target="_blank" class="btn btn-outline-primary btn-sm">
                   View Website <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                </a>
             </div>
           </div>
        </div>
        
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FrontendSettings',
  props: {
    schoolId: {
      type: Number,
      required: true
    },
    schoolCode: {
      type: String,
      required: true
    },
    schoolDomain: {
      type: String,
      required: false,
      default: ''
    }
  }
}
</script>
