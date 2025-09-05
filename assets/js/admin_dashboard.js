// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => tab.classList.remove('active'));

    // Hide all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

// Load statistics
function loadStats() {
    fetch('admin_dashboard.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-users').textContent = data.total_users;
            document.getElementById('total-organizations').textContent = data.total_organizations;
            document.getElementById('total-projects').textContent = data.total_projects;
            document.getElementById('pending-projects').textContent = data.pending_projects;
            document.getElementById('active-assignments').textContent = data.active_assignments;
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Create user form functions
function showCreateUserForm() {
    document.getElementById('create-user-form').style.display = 'block';
}

function hideCreateUserForm() {
    document.getElementById('create-user-form').style.display = 'none';
}

// Create organization form functions
function showCreateOrgForm() {
    document.getElementById('create-org-form').style.display = 'block';
}

function hideCreateOrgForm() {
    document.getElementById('create-org-form').style.display = 'none';
}

// Create project form functions
function showCreateProjectForm() {
    document.getElementById('create-project-form').style.display = 'block';
}

function hideCreateProjectForm() {
    document.getElementById('create-project-form').style.display = 'none';
}

// Edit user modal functions
function editUser(user) {
    document.getElementById('edit-user-id').value = user.user_id;
    document.getElementById('edit-name').value = user.name;
    document.getElementById('edit-username').value = user.username || '';
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-role').value = user.role;
    document.getElementById('edit-phone').value = user.phone || '';
    document.getElementById('edit-address').value = user.address || '';
    document.getElementById('edit-organization-id').value = user.organization_id || '';
    document.getElementById('edit-is-active').checked = user.is_active == 1;

    document.getElementById('edit-user-modal').style.display = 'block';
}

function closeEditUserModal() {
    document.getElementById('edit-user-modal').style.display = 'none';
}

// Edit organization modal functions
function editOrg(org) {
    document.getElementById('edit-org-id').value = org.org_id;
    document.getElementById('edit-org-name').value = org.name;
    document.getElementById('edit-org-contact-email').value = org.contact_email || '';
    document.getElementById('edit-org-contact-phone').value = org.contact_phone || '';
    document.getElementById('edit-org-description').value = org.description || '';
    document.getElementById('edit-org-address').value = org.address || '';

    document.getElementById('edit-org-modal').style.display = 'block';
}

function closeEditOrgModal() {
    document.getElementById('edit-org-modal').style.display = 'none';
}

// Project functions
function updateProjectStatus(projectId, status) {
    if (confirm('Update project status to ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_project_status">
            <input type="hidden" name="project_id" value="${projectId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    } else {
        // Reset the select if cancelled
        location.reload();
    }
}

function viewProject(projectId) {
    document.getElementById('project-details-modal').style.display = 'block';

    // Show loading state
    document.getElementById('project-details-content').innerHTML = '<div class="loading">Loading project details...</div>';

    // Fetch project details
    fetch(`admin_dashboard.php?action=get_project_details&project_id=${projectId}`)
        .then(response => response.json())
        .then(data => {
                if (data.error) {
                    document.getElementById('project-details-content').innerHTML = `<div class="alert alert-error">${data.error}</div>`;
                    return;
                }

                let volunteersHtml = '';
                if (data.volunteers && data.volunteers.length > 0) {
                    volunteersHtml = '<div class="volunteers-list"><h3>Assigned Volunteers</h3>';
                    data.volunteers.forEach(volunteer => {
                        const statusClass = volunteer.status === 'confirmed' ? 'status-badge confirmed' :
                            volunteer.status === 'completed' ? 'status-badge completed' :
                            'status-badge ' + volunteer.status;
                        volunteersHtml += `
                        <div class="volunteer-item">
                            <div class="volunteer-info">
                                <strong>${volunteer.name}</strong><br>
                                <small>${volunteer.email}</small><br>
                                <small>Assigned: ${new Date(volunteer.assigned_at).toLocaleDateString()}</small>
                            </div>
                            <span class="${statusClass}">${volunteer.status.charAt(0).toUpperCase() + volunteer.status.slice(1)}</span>
                        </div>
                    `;
                    });
                    volunteersHtml += '</div>';
                } else {
                    volunteersHtml = '<div class="volunteers-list"><h3>Assigned Volunteers</h3><p>No volunteers assigned yet.</p></div>';
                }

                const content = `
                <div class="project-details-grid">
                    <div class="project-detail-item">
                        <label>Project ID:</label>
                        <div class="value">${data.project_id}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Status:</label>
                        <div class="value"><span class="status-badge ${data.status}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span></div>
                    </div>
                    <div class="project-detail-item">
                        <label>Organization:</label>
                        <div class="value">${data.org_name || 'Guest Submission'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Priority:</label>
                        <div class="value">${data.priority ? data.priority.charAt(0).toUpperCase() + data.priority.slice(1) : 'Not specified'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Start Date:</label>
                        <div class="value">${data.start_date ? new Date(data.start_date).toLocaleDateString() : 'TBD'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>End Date:</label>
                        <div class="value">${data.end_date ? new Date(data.end_date).toLocaleDateString() : 'TBD'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Start Time:</label>
                        <div class="value">${data.start_time || 'Not specified'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>End Time:</label>
                        <div class="value">${data.end_time || 'Not specified'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Location:</label>
                        <div class="value">${data.location || 'Not specified'}</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Capacity:</label>
                        <div class="value">${data.capacity || 'Unlimited'} volunteers</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Current Volunteers:</label>
                        <div class="value">${data.confirmed_volunteers || 0} volunteers</div>
                    </div>
                    <div class="project-detail-item">
                        <label>Created:</label>
                        <div class="value">${new Date(data.created_at).toLocaleDateString()}</div>
                    </div>
                </div>
                
                <div class="project-detail-item">
                    <label>Title:</label>
                    <div class="value">${data.title}</div>
                </div>
                
                ${data.description ? `
                <div class="project-detail-item">
                    <label>Description:</label>
                    <div class="value">${data.description}</div>
                </div>
                ` : ''}
                
                ${data.skills_needed ? `
                <div class="project-detail-item">
                    <label>Skills Needed:</label>
                    <div class="value">${data.skills_needed}</div>
                </div>
                ` : ''}
                
                ${data.requirements ? `
                <div class="project-detail-item">
                    <label>Requirements:</label>
                    <div class="value">${data.requirements}</div>
                </div>
                ` : ''}
                
                ${volunteersHtml}
            `;

            document.getElementById('project-details-content').innerHTML = content;
        })
        .catch(error => {
            console.error('Error fetching project details:', error);
            document.getElementById('project-details-content').innerHTML = '<div class="alert alert-error">Error loading project details. Please try again.</div>';
        });
}

function closeProjectDetailsModal() {
    document.getElementById('project-details-modal').style.display = 'none';
}

function viewAssignment(assignmentId) {
    // This could open a modal or redirect to a detailed view
    alert('View assignment details for ID: ' + assignmentId + '\n(Feature can be expanded)');
}

// Close modals when clicking outside
window.onclick = function (event) {
    const userModal = document.getElementById('edit-user-modal');
    const orgModal = document.getElementById('edit-org-modal');
    const projectModal = document.getElementById('project-details-modal');

    if (event.target === userModal) {
        closeEditUserModal();
    }
    if (event.target === orgModal) {
        closeEditOrgModal();
    }
    if (event.target === projectModal) {
        closeProjectDetailsModal();
    }
}

// Load statistics on page load
document.addEventListener('DOMContentLoaded', function () {
    loadStats();
});