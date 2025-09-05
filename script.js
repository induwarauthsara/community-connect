// Data
let proposals = [
  {title:"Community Cleanup", by:"Jane Doe"},
  {title:"Tree Plantation", by:"John Smith"}
];

let volunteers = [
  {name:"Amal Bhathiya", email:"amal@example.com"},
  {name:"Danud Silva", email:"danud@example.com"}
];

let reviewed = 0;

// Show sections
function showSection(id) {
  document.querySelectorAll('.section').forEach(s => s.style.display = "none");
  document.getElementById(id).style.display = "block";
  updateReports();
}

// Logout
function logout() {
  alert("Logged out!");
}

// Proposals
function renderProposals() {
  let tbody = document.querySelector("#proposalTable tbody");
  tbody.innerHTML = "";
  proposals.forEach((p, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${p.title}</td>
        <td>${p.by}</td>
        <td>
          <button onclick="acceptProposal(${i})">Accept</button>
          <button class="remove" onclick="rejectProposal(${i})">Reject</button>
        </td>
      </tr>`;
  });
}

function acceptProposal(i) {
  proposals.splice(i, 1);
  reviewed++;
  renderProposals();
  updateReports();
}

function rejectProposal(i) {
  proposals.splice(i, 1);
  reviewed++;
  renderProposals();
  updateReports();
}

// Volunteers
function renderVolunteers() {
  let tbody = document.querySelector("#volunteerTable tbody");
  tbody.innerHTML = "";
  volunteers.forEach((v, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${v.name}</td>
        <td>${v.email}</td>
        <td><button class="remove" onclick="removeVolunteer(${i})">Remove</button></td>
      </tr>`;
  });
}

function removeVolunteer(i) {
  volunteers.splice(i, 1);
  renderVolunteers();
  updateReports();
}

document.getElementById("addVolunteerForm").addEventListener("submit", function(e) {
  e.preventDefault();
  let name = document.getElementById("volName").value;
  let email = document.getElementById("volEmail").value;
  volunteers.push({name, email});
  renderVolunteers();
  updateReports();
  this.reset();
});

// Reports
function updateReports() {
  document.getElementById("reportVols").textContent = volunteers.length;
  document.getElementById("reportProps").textContent = reviewed;
}

// Init
renderProposals();
renderVolunteers();
updateReports();
