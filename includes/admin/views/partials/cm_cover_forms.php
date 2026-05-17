    <div id="cmCoverRow" class="px-5 pb-5 grid sm:grid-cols-3 gap-4 border-t border-[#E3E6F0] bg-white hidden">
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverCourse" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2 font-telugu">మెయిన్ కోర్స్ కవర్ <span class="text-slate-400 font-normal">(ఐచ్ఛికం)</span></p>
        <div class="cm-cover-hitbox is-clickable mb-2" data-cover-entity="course" role="button" tabindex="0" aria-label="కోర్స్ చిత్రం జోడించండి">
          <img id="cmCoverCourseImg" alt="" class="cm-cover-preview hidden" />
          <div id="cmCoverCourseAvatar" class="cm-cover-avatar font-telugu">
            <span class="cm-cover-avatar-initials" id="cmCoverCourseInitials">—</span>
            <span class="cm-cover-avatar-label" id="cmCoverCourseLabel">కోర్స్</span>
            <span class="cm-cover-hint">చిత్రం జోడించడానికి క్లిక్ చేయండి</span>
          </div>
        </div>
        <div id="cmCoverCourseUploader" class="cm-cover-form-wrap hidden">
          <form method="post" action="<?= admin_e($apiUrl ?? admin_url('content_api.php')) ?>" enctype="multipart/form-data" class="cm-cover-form" data-entity="course">
            <input type="hidden" name="action" value="upload_image" />
            <input type="hidden" name="entity" value="course" />
            <input type="hidden" name="id" value="" class="cm-cover-id-field" />
            <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
            <input type="file" name="image_file" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="cm-cover-file text-xs w-full border border-slate-200 rounded-lg px-2 py-2" />
          </form>
        </div>
      </div>
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverSub" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2 font-telugu">సబ్ కోర్స్ కవర్ <span class="text-slate-400 font-normal">(ఐచ్ఛికం)</span></p>
        <div class="cm-cover-hitbox is-clickable mb-2" data-cover-entity="sub_course" role="button" tabindex="0" aria-label="సబ్ కోర్స్ చిత్రం జోడించండి">
          <img id="cmCoverSubImg" alt="" class="cm-cover-preview hidden" />
          <div id="cmCoverSubAvatar" class="cm-cover-avatar font-telugu">
            <span class="cm-cover-avatar-initials" id="cmCoverSubInitials">—</span>
            <span class="cm-cover-avatar-label" id="cmCoverSubLabel">సబ్ కోర్స్</span>
            <span class="cm-cover-hint">చిత్రం జోడించడానికి క్లిక్ చేయండి</span>
          </div>
        </div>
        <div id="cmCoverSubUploader" class="cm-cover-form-wrap hidden">
          <form method="post" action="<?= admin_e($apiUrl ?? admin_url('content_api.php')) ?>" enctype="multipart/form-data" class="cm-cover-form" data-entity="sub_course">
            <input type="hidden" name="action" value="upload_image" />
            <input type="hidden" name="entity" value="sub_course" />
            <input type="hidden" name="id" value="" class="cm-cover-id-field" />
            <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
            <input type="file" name="image_file" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="cm-cover-file text-xs w-full border border-slate-200 rounded-lg px-2 py-2" />
          </form>
        </div>
      </div>
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverSubject" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2 font-telugu">సబ్జెక్ట్ కవర్ <span class="text-slate-400 font-normal">(ఐచ్ఛికం)</span></p>
        <div class="cm-cover-hitbox is-clickable mb-2" data-cover-entity="subject" role="button" tabindex="0" aria-label="సబ్జెక్ట్ చిత్రం జోడించండి">
          <img id="cmCoverSubjectImg" alt="" class="cm-cover-preview hidden" />
          <div id="cmCoverSubjectAvatar" class="cm-cover-avatar font-telugu">
            <span class="cm-cover-avatar-initials" id="cmCoverSubjectInitials">—</span>
            <span class="cm-cover-avatar-label" id="cmCoverSubjectLabel">సబ్జెక్ట్</span>
            <span class="cm-cover-hint">చిత్రం జోడించడానికి క్లిక్ చేయండి</span>
          </div>
        </div>
        <div id="cmCoverSubjectUploader" class="cm-cover-form-wrap hidden">
          <form method="post" action="<?= admin_e($apiUrl ?? admin_url('content_api.php')) ?>" enctype="multipart/form-data" class="cm-cover-form" data-entity="subject">
            <input type="hidden" name="action" value="upload_image" />
            <input type="hidden" name="entity" value="subject" />
            <input type="hidden" name="id" value="" class="cm-cover-id-field" />
            <input type="hidden" name="_csrf" value="<?= admin_e(admin_csrf_token()) ?>" />
            <input type="file" name="image_file" accept="<?= admin_e(ImageUploadService::acceptAttribute()) ?>" class="cm-cover-file text-xs w-full border border-slate-200 rounded-lg px-2 py-2" />
          </form>
        </div>
      </div>
    </div>
