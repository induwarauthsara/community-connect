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
    document.getElementById('assignment-details-modal').style.display = 'block';

    // Show loading state
    document.getElementById('assignment-details-content').innerHTML = '<div class="loading">Loading assignment details...</div>';

    // Fetch assignment details
    fetch(`admin_dashboard.php?action=get_assignment_details&assignment_id=${assignmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('assignment-details-content').innerHTML = `<div class="alert alert-error">${data.error}</div>`;
                return;
            }

            const statusClass = data.status === 'confirmed' ? 'status-badge confirmed' :
                data.status === 'completed' ? 'status-badge completed' :
                data.status === 'cancelled' ? 'status-badge cancelled' :
                'status-badge registered';

            const content = `
                <div class="assignment-details-grid">
                    <div class="assignment-detail-section">
                        <h3>📋 Assignment Information</h3>
                        <div class="project-details-grid">
                            <div class="project-detail-item">
                                <label>Assignment ID:</label>
                                <div class="value">${data.id}</div>
                            </div>
                            <div class="project-detail-item">
                                <label>Status:</label>
                                <div class="value"><span class="${statusClass}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span></div>
                            </div>
                            <div class="project-detail-item">
                                <label>Assigned Date:</label>
                                <div class="value">${new Date(data.assigned_at).toLocaleDateString()}</div>
                            </div>
                            <div class="project-detail-item">
                                <label>Hours Contributed:</label>
                                <div class="value">${data.hours_contributed ? data.hours_contributed + ' hours' : 'Not recorded'}</div>
                            </div>
                            ${data.completed_at ? `
                            <div class="project-detail-item">
                                <label>Completed Date:</label>
                                <div class="value">${new Date(data.completed_at).toLocaleDateString()}</div>
                            </div>
                            ` : ''}
                        </div>
                        ${data.notes ? `
                        <div class="project-detail-item">
                            <label>Notes:</label>
                            <div class="value">${data.notes}</div>
                        </div>
                        ` : ''}
                    </div>

                    <div class="assignment-detail-section">
                        <h3>👤 Volunteer Information</h3>
                        <div class="project-details-grid">
                            <div class="project-detail-item">
                                <label>Name:</label>
                                <div class="value"><strong>${data.volunteer_name}</strong></div>
                            </div>
                            <div class="project-detail-item">
                                <label>Email:</label>
                                <div class="value"><a href="mailto:${data.volunteer_email}">${data.volunteer_email}</a></div>
                            </div>
                            ${data.volunteer_phone ? `
                            <div class="project-detail-item">
                                <label>Phone:</label>
                                <div class="value"><a href="tel:${data.volunteer_phone}">${data.volunteer_phone}</a></div>
                            </div>
                            ` : ''}
                            ${data.volunteer_address ? `
                            <div class="project-detail-item">
                                <label>Address:</label>
                                <div class="value">${data.volunteer_address}</div>
                            </div>
                            ` : ''}
                            <div class="project-detail-item">
                                <label>Joined Platform:</label>
                                <div class="value">${new Date(data.volunteer_joined).toLocaleDateString()}</div>
                            </div>
                            <div class="project-detail-item">
                                <label>Total Assignments:</label>
                                <div class="value">${data.total_assignments} project(s)</div>
                            </div>
                            <div class="project-detail-item">
                                <label>Completed Assignments:</label>
                                <div class="value">${data.completed_assignments} project(s)</div>
                            </div>
                        </div>
                    </div>

                    <div class="assignment-detail-section">
                        <h3>🚀 Project Information</h3>
                        <div class="project-details-grid">
                            <div class="project-detail-item">
                                <label>Project Title:</label>
                                <div class="value"><strong>${data.project_title}</strong></div>
                            </div>
                            <div class="project-detail-item">
                                <label>Organization:</label>
                                <div class="value">${data.org_name || 'Guest Submission'}</div>
                            </div>
                            ${data.org_email ? `
                            <div class="project-detail-item">
                                <label>Organization Contact:</label>
                                <div class="value"><a href="mailto:${data.org_email}">${data.org_email}</a></div>
                            </div>
                            ` : ''}
                            ${data.project_location ? `
                            <div class="project-detail-item">
                                <label>Location:</label>
                                <div class="value">${data.project_location}</div>
                            </div>
                            ` : ''}
                            <div class="project-detail-item">
                                <label>Start Date:</label>
                                <div class="value">${data.start_date ? new Date(data.start_date).toLocaleDateString() : 'TBD'}</div>
                            </div>
                            <div class="project-detail-item">
                                <label>End Date:</label>
                                <div class="value">${data.end_date ? new Date(data.end_date).toLocaleDateString() : 'TBD'}</div>
                            </div>
                        </div>
                        ${data.project_description ? `
                        <div class="project-detail-item">
                            <label>Project Description:</label>
                            <div class="value">${data.project_description}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <div class="assignment-actions">
                    <button type="button" class="btn btn-primary" onclick="editAssignmentStatus(${data.id}, '${data.status}')">
                        📝 Update Status
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="viewProject(${data.project_id})">
                        🔍 View Full Project
                    </button>
                    <button type="button" class="btn btn-info" onclick="contactVolunteer('${data.volunteer_email}', '${data.volunteer_name}')">
                        📧 Contact Volunteer
                    </button>
                </div>
            `;

            document.getElementById('assignment-details-content').innerHTML = content;
        })
        .catch(error => {
            console.error('Error fetching assignment details:', error);
            document.getElementById('assignment-details-content').innerHTML = '<div class="alert alert-error">Error loading assignment details. Please try again.</div>';
        });
}

function closeAssignmentDetailsModal() {
    document.getElementById('assignment-details-modal').style.display = 'none';
}

function editAssignmentStatus(assignmentId, currentStatus) {
    const statuses = ['registered', 'confirmed', 'completed', 'cancelled'];
    const statusLabels = {
        'registered': 'Registered',
        'confirmed': 'Confirmed',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };
    
    let options = '';
    statuses.forEach(status => {
        const selected = status === currentStatus ? 'selected' : '';
        options += `<option value="${status}" ${selected}>${statusLabels[status]}</option>`;
    });
    
    const newStatus = prompt(`Select new status for assignment #${assignmentId}:\n\nCurrent Status: ${statusLabels[currentStatus]}\n\nEnter new status:`, currentStatus);
    
    if (newStatus && statuses.includes(newStatus) && newStatus !== currentStatus) {
        if (confirm(`Change assignment status from "${statusLabels[currentStatus]}" to "${statusLabels[newStatus]}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_assignment_status">
                <input type="hidden" name="assignment_id" value="${assignmentId}">
                <input type="hidden" name="status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function contactVolunteer(email, name) {
    const subject = encodeURIComponent(`Community Connect - Volunteer Assignment`);
    const body = encodeURIComponent(`Dear ${name},\n\nI hope this message finds you well.\n\nRegarding your volunteer assignment through Community Connect:\n\n\n\nBest regards,\nCommunity Connect Admin`);
    
    window.open(`mailto:${email}?subject=${subject}&body=${body}`, '_blank');
}

// Close modals when clicking outside
window.onclick = function (event) {
    const userModal = document.getElementById('edit-user-modal');
    const orgModal = document.getElementById('edit-org-modal');
    const projectModal = document.getElementById('project-details-modal');
    const assignmentModal = document.getElementById('assignment-details-modal');
    const editProjectModal = document.getElementById('edit-project-modal');

    if (event.target === userModal) {
        closeEditUserModal();
    }
    if (event.target === orgModal) {
        closeEditOrgModal();
    }
    if (event.target === projectModal) {
        closeProjectDetailsModal();
    }
    if (event.target === assignmentModal) {
        closeAssignmentDetailsModal();
    }
    if (event.target === editProjectModal) {
        closeEditProjectModal();
    }
}

// Edit project functions
function editProject(project) {
    document.getElementById('edit-project-id').value = project.project_id;
    document.getElementById('edit-project-title').value = project.title || '';
    document.getElementById('edit-project-location').value = project.location || '';
    document.getElementById('edit-project-start-date').value = project.start_date || '';
    document.getElementById('edit-project-end-date').value = project.end_date || '';
    document.getElementById('edit-project-start-time').value = project.start_time || '';
    document.getElementById('edit-project-end-time').value = project.end_time || '';
    document.getElementById('edit-project-capacity').value = project.capacity || '';
    document.getElementById('edit-project-priority').value = project.priority || 'medium';
    document.getElementById('edit-project-organization-id').value = project.organization_id || '';
    document.getElementById('edit-project-skills-needed').value = project.skills_needed || '';
    document.getElementById('edit-project-requirements').value = project.requirements || '';
    document.getElementById('edit-project-description').value = project.description || '';
    
    document.getElementById('edit-project-modal').style.display = 'block';
}

function closeEditProjectModal() {
    document.getElementById('edit-project-modal').style.display = 'none';
}

// Load statistics on page load
document.addEventListener('DOMContentLoaded', function () {
    loadStats();
});