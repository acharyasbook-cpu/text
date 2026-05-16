    <div id="cmCoverRow" class="px-5 pb-5 grid sm:grid-cols-3 gap-4 border-t border-[#E3E6F0] bg-white hidden">
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverCourse" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2 font-telugu">మెయిన్ కోర్స్ కవర్</p>
        <div class="h-28 bg-white border border-dashed border-[#E3E6F0] rounded-lg overflow-hidden mb-2 classical-card-media">
          <img id="cmCoverCourseImg" alt="" class="w-full h-full object-cover hidden" />
        </div>
        <form method="post" action="<?= admin_e($apiUrl ?? admin_url('content_api.php')) ?>" enctype="multipart/form-data" class="cm-cover-form" data-entity="course">
          <input type="hidden" name="action" value="upload_image" />
          <input type="hidden" name="entity" value="course" />
          <input type="hidden" name="id" value="" class="cm-cover-id-field" />
          <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
          <input type="file" name="image_file" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="cm-cover-file text-xs w-full" />
        </form>
      </div>
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverSub" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2 font-telugu">సబ్ కోర్స్ కవర్</p>
        <div class="h-28 bg-white border border-dashed border-[#E3E6F0] rounded-lg overflow-hidden mb-2 classical-card-media">
          <img id="cmCoverSubImg" alt="" class="w-full h-full object-cover hidden" />
        </div>
        <form method="post" action="<?= admin_e($apiUrl ?? admin_url('content_api.php')) ?>" enctype="multipart/form-data" class="cm-cover-form" data-entity="sub_course">
          <input type="hidden" name="action" value="upload_image" />
          <input type="hidden" name="entity" value="sub_course" />
          <input type="hidden" name="id" value="" class="cm-cover-id-field" />
          <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
          <input type="file" name="image_file" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="cm-cover-file text-xs w-full" />
        </form>
      </div>
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverSubject" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2 font-telugu">సబ్జెక్ట్ కవర్</p>
        <div class="h-28 bg-white border border-dashed border-[#E3E6F0] rounded-lg overflow-hidden mb-2 classical-card-media">
          <img id="cmCoverSubjectImg" alt="" class="w-full h-full object-cover hidden" />
        </div>
        <form method="post" action="<?= admin_e($apiUrl ?? admin_url('content_api.php')) ?>" enctype="multipart/form-data" class="cm-cover-form" data-entity="subject">
          <input type="hidden" name="action" value="upload_image" />
          <input type="hidden" name="entity" value="subject" />
          <input type="hidden" name="id" value="" class="cm-cover-id-field" />
          <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
          <input type="file" name="image_file" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="cm-cover-file text-xs w-full" />
        </form>
      </div>
    </div>
