@php
    $genres = empty($genres ?? []) ? \App\Models\MusicDemo::GENRES : $genres;
    $demoGenres = collect($genres)->reject(fn ($genre) => $genre === \App\Models\MusicDemo::GENRE_NONE);
    $categories = empty($categories ?? []) ? \App\Models\StyleSampling::CATEGORIES : $categories;
    $packs = empty($packs ?? []) ? \App\Models\StyleSampling::PACKS : $packs;
    $styleProducts = collect($styleProducts ?? []);
    $uploadStyleErrors = isset($errors)
        ? $errors->uploadStyle
        : new \Illuminate\Support\MessageBag();
@endphp

@if(request()->routeIs('admin.demo'))
    <div class="modal fade" id="uploadDemoModal" tabindex="-1" aria-labelledby="uploadDemoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content modal-form" action="{{ route('admin.demo.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Demo video</span>
                        <h2 class="modal-title" id="uploadDemoModalLabel">Add New Demo</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr>
                <div class="modal-body">
                    <label>
                        YouTube Video URL
                        <input type="url" name="youtube_url" value="{{ old('youtube_url') }}" placeholder="https://www.youtube.com/watch?v=...">
                    </label>

                    <label class="modal-file">
                        MP4 Video Upload
                        <input type="file" name="installation_video" accept="video/mp4">
                        <span>Optional .mp4 up to 200MB. Add a YouTube URL or upload an MP4 video.</span>
                    </label>

                    <div class="modal-grid">
                        <label>
                            Demo Title
                            <input type="text" name="title" value="{{ old('title') }}" placeholder="Example: Dangdut Raya" required>
                        </label>

                        <label>
                            Genre
                            <select name="genre">
                                <option value="" @selected(blank(old('genre')))>None</option>
                                @foreach($demoGenres as $genre)
                                    <option value="{{ $genre }}" @selected(old('genre') === $genre)>{{ $genre }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="modal-grid">
                        <label>
                            BPM
                            <input type="number" name="bpm" value="{{ old('bpm') }}" placeholder="140" min="1" max="300">
                        </label>

                        <label>
                            Duration
                            <input type="text" name="duration" value="{{ old('duration') }}" placeholder="3:24" pattern="\d{1,3}:[0-5]\d" required>
                        </label>
                    </div>

                    <div class="modal-grid">
                        <label>
                            Key Signature
                            <input type="text" name="key_signature" value="{{ old('key_signature') }}" placeholder="Example: C Minor">
                        </label>

                        <label>
                            Status
                            <select name="status" required>
                                <option value="Published" @selected(old('status', 'Published') === 'Published')>Published</option>
                                <option value="Draft" @selected(old('status') === 'Draft')>Draft</option>
                            </select>
                        </label>
                    </div>

                    <p class="modal-context">Thumbnail YouTube diambil otomatis. MP4 memakai cover demo default.</p>

                    <label class="modal-toggle">
                        <input type="checkbox" name="trending">
                        <span></span>
                        Set as trending demo
                    </label>
                </div>
                <hr>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit">Save Demo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="demoEditModal" tabindex="-1" aria-labelledby="demoEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="modal-form" action="#" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h2 class="modal-title" id="demoEditModalLabel">Edit Demo</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <hr>
                    <div class="modal-body">
                        <label>
                            Demo Title
                            <input type="text" name="title" data-demo-edit-title required>
                        </label>

                        <label>
                            YouTube Video URL
                            <input type="url" name="youtube_url" data-demo-edit-youtube-url>
                        </label>

                        <label class="modal-file">
                            Replace MP4 Video
                            <input type="file" name="installation_video" accept="video/mp4" data-demo-edit-installation-video>
                            <span data-demo-edit-installation-status>No MP4 video yet. Upload one to show the MP4 video button.</span>
                        </label>

                        <div class="modal-grid">
                            <label>
                                Genre
                                <select name="genre" data-demo-edit-genre>
                                    <option value="">None</option>
                                    @foreach($demoGenres as $genre)
                                        <option value="{{ $genre }}">{{ $genre }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label>
                                BPM
                                <input type="number" name="bpm" min="1" max="300" data-demo-edit-bpm>
                            </label>
                        </div>

                        <div class="modal-grid">
                            <label>
                                Duration
                                <input type="text" name="duration" placeholder="3:24" pattern="\d{1,3}:[0-5]\d" data-demo-edit-duration required>
                            </label>

                            <label>
                                Key Signature
                                <input type="text" name="key_signature" placeholder="Example: C Minor" data-demo-edit-key-signature>
                            </label>
                        </div>

                        <label>
                            Status
                            <select name="status" data-demo-edit-status required>
                                <option value="Published">Published</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </label>

                        <label class="modal-toggle">
                            <input type="hidden" name="trending" value="0">
                            <input type="checkbox" name="trending" value="1" data-demo-edit-trending>
                            <span></span>
                            Mark as trending
                        </label>

                        <label class="modal-toggle">
                            <input type="checkbox" name="remove_installation_video" value="1" data-demo-edit-remove-installation>
                            <span></span>
                            Remove MP4 video
                        </label>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="demoDeleteModal" tabindex="-1" aria-labelledby="demoDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="modal-form modal-form--danger" action="#" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h2 class="modal-title" id="demoDeleteModalLabel">Delete Demo</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <hr>
                    <div class="modal-body">
                        <div class="modal-warning">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                            <div>
                                <strong>Delete <span data-demo-modal-title>this demo</span>?</strong>
                                <p>This removes the demo from the admin table and customer demo library.</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">Delete Demo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if(request()->routeIs('admin.stylesampling'))
    <div class="modal fade" id="uploadStyleModal" tabindex="-1" aria-labelledby="uploadStyleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content modal-form" action="{{ route('admin.stylesampling.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Style file catalog</span>
                        <h2 class="modal-title" id="uploadStyleModalLabel">Upload New Style</h2>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr>
                <div class="modal-body">
                    @if($uploadStyleErrors->any())
                        <div class="modal-alert modal-alert--danger">
                            @foreach($uploadStyleErrors->all() as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif

                    <label class="modal-dropzone">
                        <input type="file" name="style_file" accept=".sty,.prs,.sst" data-file-label="#styleFileName">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <path d="M17 8l-5-5-5 5" />
                            <path d="M12 3v12" />
                        </svg>
                        <strong id="styleFileName">Choose style file</strong>
                        <span>.sty up to 50MB</span>
                    </label>

                    <div class="modal-grid">
                        <label>
                            Style Name
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Example: HM Dangdut Raya">
                        </label>

                        <label>
                            Category
                            <select name="category">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category')===$category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            Expansion Pack
                            <select name="pack">
                                <option value="">Select Pack</option>
                                @foreach($packs as $pack)
                                    <option value="{{ $pack }}" @selected(old('pack')===$pack)>{{ $pack }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label>
                        Cover Image
                        <input type="file" name="cover_image" accept="image/*">
                    </label>

                    <label>
                        Description
                        <textarea name="description" rows="4" placeholder="Short note for this style">{{ old('description') }}</textarea>
                    </label>

                    <label class="modal-toggle">
                        <input type="checkbox" name="published" value="1" @checked(old('published', true))>
                        <span></span>
                        Publish to catalog
                    </label>
                </div>
                <hr>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit">Save New Style</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="styleSamplingEditModal" tabindex="-1" aria-labelledby="styleSamplingEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="modal-form" action="#" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h2 class="modal-title" id="styleSamplingEditModalLabel">Edit Style</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <hr>
                    <div class="modal-body">
                        <p class="modal-context" data-style-modal-name>Selected style</p>

                        <label>
                            Style Name
                            <input type="text" name="name" data-style-edit-name>
                        </label>

                        <label>
                            Category
                            <select name="category" data-style-edit-category>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            Description
                            <textarea name="description" rows="4" placeholder="Update notes for this style."></textarea>
                        </label>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="styleSamplingDeleteModal" tabindex="-1" aria-labelledby="styleSamplingDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="modal-form modal-form--danger" action="#" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h2 class="modal-title" id="styleSamplingDeleteModalLabel">Delete Style</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <hr>
                    <div class="modal-body">
                        <div class="modal-warning">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                            <div>
                                <strong>Delete <span data-style-modal-name>this style</span>?</strong>
                                <p>This only removes the style catalog row. Customer N27 requests stay in the N27 Requests page.</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">Delete Style</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if(request()->routeIs('admin.sampling-requests'))
    <div class="modal fade" id="samplingPaymentModal" tabindex="-1" aria-labelledby="samplingPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="modal-form" action="#" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <div>
                            <span class="modal-eyebrow">Sampling Payment</span>
                            <h2 class="modal-title" id="samplingPaymentModalLabel">Confirm Request Payment</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <hr>
                    <div class="modal-body">
                        <p class="modal-context" data-sampling-payment-context>Selected sampling request</p>

                        <label>
                            Sampling Price
                            <input type="number" name="amount" min="0" step="1000" placeholder="{{ \App\Models\StyleSampling::SAMPLING_REQUEST_PRICE }}" data-sampling-payment-amount required>
                        </label>

                        <label>
                            Admin Note
                            <textarea name="admin_notes" rows="4" placeholder="Harga tetap Rp {{ number_format(\App\Models\StyleSampling::SAMPLING_REQUEST_PRICE, 0, ',', '.') }}, ukuran pack, dan instruksi pembayaran." data-sampling-payment-notes></textarea>
                        </label>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">Mark as Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="samplingDeliveryModal" tabindex="-1" aria-labelledby="samplingDeliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="modal-form modal-form--delivery" action="#" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <div>
                            <span class="modal-eyebrow">Google Drive Delivery</span>
                            <h2 class="modal-title" id="samplingDeliveryModalLabel">Send Completed Sampling</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <hr>
                    <div class="modal-body">
                        <p class="modal-context" data-sampling-context>Selected sampling request</p>

                        <label>
                            Google Drive Link
                            <input type="url" name="google_drive_link" placeholder="https://drive.google.com/..." data-sampling-link-input required>
                        </label>

                        <label>
                            Delivery Status
                            <select name="status">
                                <option value="{{ \App\Models\SamplingRequest::STATUS_READY }}">Ready</option>
                                <option value="{{ \App\Models\SamplingRequest::STATUS_COMPLETED }}">Completed</option>
                            </select>
                        </label>

                        <label>
                            Notes for Customer
                            <textarea name="delivery_notes" rows="4" placeholder="Optional download or installation notes." data-sampling-notes-input></textarea>
                        </label>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">Save & Send Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if(request()->routeIs('admin.usermanagement'))
    @php
        $userStatuses = ['Active', 'Suspended', 'Review'];
        $userPlans = ['Free', 'Starter Monthly', 'Premium Monthly', 'Premium 90 Days', 'Premium Yearly', 'Studio Pro'];
    @endphp

    <div class="modal fade user-manage-modal" id="userManageModal" tabindex="-1" aria-labelledby="userManageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">User Management</span>
                        <h2 class="modal-title" id="userManageModalLabel" data-user-name>Selected user</h2>
                        <p class="user-manage-modal__email" data-user-email>-</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr>

                <div class="modal-body">
                    <div class="user-manage-overview">
                        <div>
                            <span>Plan</span>
                            <strong data-user-profile-plan>-</strong>
                        </div>
                        <div>
                            <span>Status</span>
                            <strong data-user-profile-status>-</strong>
                        </div>
                        <div>
                            <span>Joined</span>
                            <strong data-user-profile-created>-</strong>
                        </div>
                        <div>
                            <span>Expires</span>
                            <strong data-user-profile-expires>-</strong>
                        </div>
                    </div>

                    <div class="user-manage-grid">
                        <section>
                            <span>Subscription</span>
                            <dl>
                                <div><dt>Package</dt><dd data-subscription-package>-</dd></div>
                                <div><dt>Status</dt><dd data-subscription-status>-</dd></div>
                                <div><dt>Starts</dt><dd data-subscription-starts>-</dd></div>
                                <div><dt>Expires</dt><dd data-subscription-expires>-</dd></div>
                            </dl>
                        </section>

                        <section>
                            <span>Latest Payment</span>
                            <dl>
                                <div><dt>Reference</dt><dd data-payment-reference>-</dd></div>
                                <div><dt>Status</dt><dd data-payment-status>-</dd></div>
                                <div><dt>Amount</dt><dd data-payment-amount>-</dd></div>
                                <div><dt>Method</dt><dd data-payment-method>-</dd></div>
                            </dl>
                        </section>

                        <section>
                            <span>Latest N27</span>
                            <dl>
                                <div><dt>Order</dt><dd data-sampling-reference>-</dd></div>
                                <div><dt>Status</dt><dd data-sampling-status>-</dd></div>
                                <div><dt>Payment</dt><dd data-sampling-payment-status>-</dd></div>
                                <div><dt>Product</dt><dd data-sampling-product>-</dd></div>
                            </dl>
                        </section>

                        <section>
                            <span>Activity</span>
                            <dl>
                                <div><dt>Requests</dt><dd data-user-profile-requests>-</dd></div>
                                <div><dt>Downloads</dt><dd data-user-profile-downloads>-</dd></div>
                                <div><dt>Revenue</dt><dd data-user-profile-revenue>-</dd></div>
                                <div><dt>Last</dt><dd data-user-profile-activity>-</dd></div>
                            </dl>
                        </section>
                    </div>

                    <ul class="nav nav-tabs user-manage-tabs" id="userManageTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="access-user-tab" data-bs-toggle="tab" data-bs-target="#accessUserPane" type="button" role="tab" aria-controls="accessUserPane" aria-selected="true">Access</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="status-user-tab" data-bs-toggle="tab" data-bs-target="#statusUserPane" type="button" role="tab" aria-controls="statusUserPane" aria-selected="false">Status</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="plan-user-tab" data-bs-toggle="tab" data-bs-target="#planUserPane" type="button" role="tab" aria-controls="planUserPane" aria-selected="false">Plan</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-user-tab" data-bs-toggle="tab" data-bs-target="#passwordUserPane" type="button" role="tab" aria-controls="passwordUserPane" aria-selected="false">Password</button>
                        </li>
                    </ul>

                    <div class="tab-content user-manage-tab-content">
                        <div class="tab-pane fade show active" id="accessUserPane" role="tabpanel" aria-labelledby="access-user-tab" tabindex="0">
                            <form class="user-manage-action" action="#" method="POST" data-user-access-form>
                                @csrf
                                @method('PATCH')

                                <div class="user-manage-action__summary">
                                    <div>
                                        <span>Payment</span>
                                        <strong data-payment-status>-</strong>
                                        <small data-payment-reference>-</small>
                                    </div>
                                    <div>
                                        <span>Package</span>
                                        <strong data-payment-package>-</strong>
                                        <small data-payment-date>-</small>
                                    </div>
                                </div>

                                <div class="user-manage-action__empty" data-show-when-legacy-access>
                                    <strong>Sync legacy pending access</strong>
                                    <p>Pending legacy subscription payment is completed and STY access is restored. Sampling request payments remain separate.</p>
                                </div>

                                <button type="submit">Sync Access</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="statusUserPane" role="tabpanel" aria-labelledby="status-user-tab" tabindex="0">
                            <form class="user-manage-action" action="#" method="POST" data-user-status-form>
                                @csrf
                                @method('PATCH')

                                <div class="modal-grid">
                                    <label>
                                        Account Status
                                        <select name="status" data-user-status-input required>
                                            @foreach($userStatuses as $status)
                                                <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label>
                                        Admin Note
                                        <input type="text" name="admin_note" maxlength="240" data-user-status-note-input>
                                    </label>
                                </div>

                                <label class="modal-toggle">
                                    <input type="checkbox" name="cancel_subscription" value="1" data-user-cancel-subscription-input>
                                    <span></span>
                                    Cancel active subscription when suspending
                                </label>

                                <button type="submit">Save Status</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="planUserPane" role="tabpanel" aria-labelledby="plan-user-tab" tabindex="0">
                            <form class="user-manage-action" action="#" method="POST" data-user-plan-form>
                                @csrf
                                @method('PATCH')

                                <div class="modal-grid">
                                    <label>
                                        Plan
                                        <select name="plan" data-user-plan-input required>
                                            @foreach($userPlans as $plan)
                                                <option value="{{ $plan }}">{{ $plan }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label>
                                        Expired Date
                                        <input type="date" name="expires_at" data-user-expires-input>
                                    </label>
                                </div>

                                <div class="user-manage-action__summary">
                                    <div>
                                        <span>Latest Subscription</span>
                                        <strong data-subscription-status>-</strong>
                                        <small data-subscription-expires>-</small>
                                    </div>
                                    <div>
                                        <span>Manual Result</span>
                                        <strong>Active</strong>
                                        <small>New subscription row</small>
                                    </div>
                                </div>

                                <button type="submit">Save Plan</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="passwordUserPane" role="tabpanel" aria-labelledby="password-user-tab" tabindex="0">
                            <form class="user-manage-action user-manage-action--danger" action="#" method="POST" data-user-password-form>
                                @csrf
                                @method('PATCH')

                                <div class="modal-grid">
                                    <label>
                                        New Password
                                        <input type="password" name="password" autocomplete="new-password" minlength="8" data-user-password-input required>
                                    </label>

                                    <label>
                                        Confirm Password
                                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" data-user-password-confirmation-input required>
                                    </label>
                                </div>

                                <button type="submit">Reset Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="modal-footer user-manage-modal__footer">
                    <button type="button" data-bs-dismiss="modal">Close</button>
                    <button type="button" onclick="window.location.href" ='{{ route('admin.sampling-requests') }}'">Open N27 Queue</button>
                </div>
            </div>
        </div>
    </div>
@endif

@if(request()->routeIs('subcription'))
    @php
        $premiumPlan = ($plans ?? [])['premium_monthly'] ?? [
            'name' => 'Premium Monthly',
            'price' => \App\Models\SiteSetting::DEFAULT_SUBSCRIPTION_PRICE,
        ];
        $checkoutUser = auth()->user();
        $hasActiveSubscription = $hasActiveSubscription ?? false;
        $checkoutModeLabel = $checkoutUser
            ? ($hasActiveSubscription ? 'Extend STY Access' : 'Renew STY Access')
            : 'Register or Renew STY';
    @endphp
    <div class="modal fade" id="subscriptionPaymentModal" tabindex="-1" aria-labelledby="subscriptionPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Premium Payment</span>
                        <h2 class="modal-title" id="subscriptionPaymentModalLabel">{{ $checkoutModeLabel }}</h2>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form class="modal-form" action="{{ route('subcription.payment') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="package" value="{{ $premiumPlan['name'] }}">
                    <input type="hidden" name="amount" value="{{ (int) $premiumPlan['price'] }}">
                    <input type="hidden" name="method" value="Midtrans Snap Sandbox">
                    <hr>
                    <div class="modal-body">
                        <div class="modal-summary">
                            <div>
                                <span>Selected Plan</span>
                                <strong>{{ $premiumPlan['name'] }}</strong>
                            </div>

                            <div>
                                <span>Midtrans Payment</span>
                                <strong>Rp {{ number_format((int) $premiumPlan['price'], 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="social-register">
                            @if($checkoutUser?->hasSocialLogin())
                                <div class="social-register__connected">
                                    <strong>{{ $checkoutUser->socialLoginProviderLabel() }} connected</strong>
                                    <span>{{ $checkoutUser->email }}</span>
                                </div>
                            @else
                                <div class="social-register__actions">
                                    <a class="social-register__button" href="{{ route('auth.google.redirect', ['intent' => 'subscription']) }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="#4285F4" d="M22.6 12.23c0-.78-.07-1.53-.2-2.23H12v4.26h5.94c-.26 1.37-1.04 2.53-2.21 3.31v2.75h3.58c2.09-1.93 3.29-4.77 3.29-8.09Z" />
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.68l-3.58-2.75c-.98.66-2.24 1.06-3.7 1.06-2.84 0-5.25-1.92-6.11-4.5H2.2v2.84C4.01 20.55 7.72 23 12 23Z" />
                                            <path fill="#FBBC05" d="M5.89 14.13A6.6 6.6 0 0 1 5.53 12c0-.74.13-1.45.36-2.13V7.03H2.2A10.95 10.95 0 0 0 1 12c0 1.78.43 3.46 1.2 4.97l3.69-2.84Z" />
                                            <path fill="#EA4335" d="M12 5.37c1.62 0 3.06.56 4.21 1.64l3.16-3.16C17.45 2.06 14.97 1 12 1 7.72 1 4.01 3.45 2.2 7.03l3.69 2.84C6.75 7.29 9.16 5.37 12 5.37Z" />
                                        </svg>
                                        <span>Google</span>
                                    </a>
                                    <a class="social-register__button social-register__button--facebook" href="{{ route('auth.facebook.redirect', ['intent' => 'subscription']) }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="currentColor" d="M13.62 22v-8.77h2.94l.44-3.42h-3.38V7.63c0-.99.28-1.66 1.69-1.66h1.8V2.92c-.31-.04-1.38-.13-2.62-.13-2.59 0-4.37 1.58-4.37 4.49v2.53H7.18v3.42h2.94V22h3.5Z" />
                                        </svg>
                                        <span>Facebook</span>
                                    </a>
                                </div>
                                <p>Pilih akun Google atau Facebook yang sedang terhubung di browser. Email akan diisi dan ditautkan otomatis.</p>
                            @endif
                        </div>

                        <div class="modal-section">
                            <span>Customer Data</span>
                            <h3>Registration or Renewal</h3>
                        </div>

                        <div class="modal-grid">
                            <label>
                                Full Name
                                <input type="text" name="name" value="{{ old('name', $checkoutUser?->name) }}" placeholder="Your name" required>
                                @error('name')<small>{{ $message }}</small>@enderror
                            </label>

                            <label>
                                Email / Gmail
                                <input id="subscription-email" type="email" name="email" value="{{ old('email', $checkoutUser?->email) }}" placeholder="name@gmail.com" @readonly($checkoutUser) required>
                                @error('email')<small>{{ $message }}</small>@enderror
                            </label>

                            @unless($checkoutUser?->hasSocialLogin())
                                <label>
                                    Password
                                    <input type="password" name="password" placeholder="{{ $checkoutUser ? 'Confirm current password' : 'Create new or enter current password' }}" required>
                                    @error('password')<small>{{ $message }}</small>@enderror
                                </label>

                                <label>
                                    Confirm Password
                                    <input type="password" name="password_confirmation" placeholder="Repeat password" required>
                                </label>
                            @endunless

                            <label>
                                Phone
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+62">
                                @error('phone')<small>{{ $message }}</small>@enderror
                            </label>

                            <label class="modal-file">
                                Profile Photo
                                <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                                <span>JPG, PNG, or WEBP up to 2 MB. If empty, avatar is generated from your name.</span>
                                @error('profile_photo')<small>{{ $message }}</small>@enderror
                            </label>

                        </div>
                    </div>
                    <hr>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit">{{ $checkoutModeLabel }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
