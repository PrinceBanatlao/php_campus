document.addEventListener('DOMContentLoaded', () => {
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const reportLostBtn = document.getElementById('reportLostBtn');
    const reportFoundBtn = document.getElementById('reportFoundBtn');
    const reportModal = document.getElementById('reportModal');
    const reportModalTitle = document.getElementById('reportModalTitle');
    const reportType = document.getElementById('reportType');
    const submitReport = document.getElementById('submitReport');
    const closeReportModal = document.getElementById('closeReportModal');
    const manageModal = document.getElementById('manageModal');
    const manageModalTitle = document.getElementById('manageModalTitle');
    const manageModalContent = document.getElementById('manageModalContent');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    const saveProfile = document.getElementById('saveProfile');
    const cancelEditProfile = document.getElementById('cancelEditProfile');
    const searchBar = document.getElementById('searchBar');
    const clearSearch = document.getElementById('clearSearch');
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    const notificationNav = document.getElementById('notificationNav');
    const notificationModal = document.getElementById('notificationModal');
    const closeNotificationModal = document.getElementById('closeNotificationModal');
    const notificationContent = document.getElementById('notificationContent');
    const editButton = document.getElementById('editButton');
    const deleteButton = document.getElementById('deleteButton');
    const approveButton = document.getElementById('approveButton');
    const myReportsContainer = document.getElementById('myReportsContainer');
    const customModal = document.getElementById('customModal');
    const customModalMessage = document.getElementById('customModalMessage');
    const customModalButtons = document.getElementById('customModalButtons');
    const customModalConfirm = document.getElementById('customModalConfirm');
    const customModalCancel = document.getElementById('customModalCancel');
    const customModalOK = document.getElementById('customModalOK');
    const customModalClose = document.getElementById('customModalClose');
    const userId = document.body.dataset.userId || '';

    function validateReportForm() {
        const fields = [
            { id: 'reportItemName', errorId: 'reportItemNameError', message: 'Item name is required' },
            { id: 'reportDescription', errorId: 'reportDescriptionError', message: 'Description is required' },
            { id: 'reportLocation', errorId: 'reportLocationError', message: 'Location is required' },
            { id: 'reportDate', errorId: 'reportDateError', message: 'Date is required' }
        ];
        let isValid = true;
        fields.forEach(field => {
            const input = document.getElementById(field.id).value.trim();
            const errorEl = document.getElementById(field.errorId);
            if (!input) {
                errorEl.textContent = field.message;
                errorEl.classList.remove('hidden');
                isValid = false;
            } else {
                errorEl.classList.add('hidden');
            }
        });
        return isValid;
    }

    function validateProfileForm() {
        const name = document.getElementById('editName').value.trim();
        const email = document.getElementById('editEmail').value.trim();
        const nameError = document.getElementById('editNameError');
        const emailError = document.getElementById('editEmailError');
        let isValid = true;

        if (!name) {
            nameError.textContent = 'Name is required';
            nameError.classList.remove('hidden');
            isValid = false;
        } else {
            nameError.classList.add('hidden');
        }

        if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
            emailError.textContent = 'Valid email is required';
            emailError.classList.remove('hidden');
            isValid = false;
        } else {
            emailError.classList.add('hidden');
        }

        return isValid;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function clearReportErrors() {
        ['reportItemNameError', 'reportDescriptionError', 'reportLocationError', 'reportDateError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
                el.textContent = '';
            }
        });
    }

    function showCustomAlert(message) {
        if (!customModal || !customModalMessage || !customModalButtons || !customModalOK) return;
        customModalMessage.textContent = message;
        customModalConfirm.classList.add('hidden');
        customModalCancel.classList.add('hidden');
        customModalOK.classList.remove('hidden');
        customModal.style.display = 'block';
    }

    function showCustomConfirm(message) {
        return new Promise((resolve) => {
            if (!customModal || !customModalMessage || !customModalButtons || !customModalConfirm || !customModalCancel) {
                resolve(false);
                return;
            }
            customModalMessage.textContent = message;
            customModalOK.classList.add('hidden');
            customModalConfirm.classList.remove('hidden');
            customModalCancel.classList.remove('hidden');
            customModal.style.display = 'block';

            const confirmHandler = () => {
                customModal.style.display = 'none';
                customModalConfirm.removeEventListener('click', confirmHandler);
                customModalCancel.removeEventListener('click', cancelHandler);
                resolve(true);
            };
            const cancelHandler = () => {
                customModal.style.display = 'none';
                customModalConfirm.removeEventListener('click', confirmHandler);
                customModalCancel.removeEventListener('click', cancelHandler);
                resolve(false);
            };
            customModalConfirm.addEventListener('click', confirmHandler);
            customModalCancel.addEventListener('click', cancelHandler);
        });
    }

    function manageReport(itemId) {
        if (!itemId) {
            console.error('Invalid itemId:', itemId);
            showCustomAlert('Invalid item ID.');
            return;
        }
        fetch(`get_item.php?id=${itemId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(item => {
                if (item.error) {
                    showCustomAlert(item.error);
                    return;
                }
                manageModalTitle.textContent = `${item.type.charAt(0).toUpperCase() + item.type.slice(1)} Item: ${item.name || 'Unnamed Item'}`;
                document.getElementById('manageModalDescriptionText').textContent = item.description || 'No description available';
                document.getElementById('manageModalLocationText').textContent = item.location || 'Not specified';
                document.getElementById('manageModalDateText').textContent = item.date || 'Not specified';
                document.getElementById('manageModalStatusText').textContent = item.status || 'Unknown';
                const manageModalImage = document.getElementById('manageModalImage');
                if (item.image) {
                    manageModalImage.src = item.image;
                    manageModalImage.classList.remove('hidden');
                } else {
                    manageModalImage.classList.add('hidden');
                }
                document.getElementById('reportItemId').value = item.id;
                if (editButton) editButton.classList.remove('hidden');
                if (deleteButton) deleteButton.classList.remove('hidden');
                manageModal.style.display = 'block';

                if (editButton && deleteButton) {
                    editButton.onclick = () => {
                        if (item.status !== 'pending') {
                            showCustomAlert('This item cannot be edited as it has been approved or matched.');
                            return;
                        }
                        reportModalTitle.textContent = `Edit ${item.type.charAt(0).toUpperCase() + item.type.slice(1)} Item`;
                        reportType.value = item.type;
                        document.getElementById('reportItemName').value = item.name || '';
                        document.getElementById('reportDescription').value = item.description || '';
                        document.getElementById('reportLocation').value = item.location || '';
                        document.getElementById('reportDate').value = item.date || '';
                        manageModal.style.display = 'none';
                        reportModal.style.display = 'block';
                    };

                    deleteButton.onclick = async () => {
                        const confirmed = await showCustomConfirm('Are you sure you want to delete this report?');
                        if (confirmed) {
                            fetch('delete_report.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `item_id=${itemId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const reportElement = document.getElementById(`report-${itemId}`);
                                    if (reportElement) {
                                        reportElement.style.transition = 'opacity 0.3s';
                                        reportElement.style.opacity = '0';
                                        setTimeout(() => {
                                            reportElement.remove();
                                            manageModal.style.display = 'none';
                                            showCustomAlert('Report deleted successfully.');
                                        }, 300);
                                    }
                                } else {
                                    showCustomAlert(data.message || 'Failed to delete report.');
                                }
                            })
                            .catch(error => {
                                console.error('Error deleting report:', error);
                                showCustomAlert('An error occurred while deleting the report.');
                            });
                        }
                    };
                }

                if (approveButton) {
                    approveButton.onclick = async () => {
                        const confirmed = await showCustomConfirm('Are you sure you want to approve this report?');
                        if (confirmed) {
                            fetch('home.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `action=approve_report&item_id=${itemId}`
                            })
                            .then(response => response.text())
                            .then(() => {
                                manageModal.style.display = 'none';
                                showCustomAlert('Report approved successfully.');
                                location.reload();
                            })
                            .catch(error => {
                                console.error('Error approving report:', error);
                                showCustomAlert('An error occurred while approving the report.');
                            });
                        }
                    };
                }
            })
            .catch(error => {
                console.error('Error fetching item details:', error);
                showCustomAlert('Failed to load item details. Please try again.');
            });
    }

    function viewItem(itemId, isViewMode) {
        if (!itemId) {
            console.error('Invalid itemId:', itemId);
            showCustomAlert('Invalid item ID.');
            return;
        }
        fetch(`get_item.php?id=${itemId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(item => {
                if (item.error) {
                    showCustomAlert(item.error);
                    return;
                }
                manageModalTitle.textContent = `${item.type.charAt(0).toUpperCase() + item.type.slice(1)} Item: ${item.name || 'Unnamed Item'}`;
                document.getElementById('manageModalDescriptionText').textContent = item.description || 'No description available';
                document.getElementById('manageModalLocationText').textContent = item.location || 'Not specified';
                document.getElementById('manageModalDateText').textContent = item.date || 'Not specified';
                document.getElementById('manageModalStatusText').textContent = item.status || 'Unknown';
                const manageModalImage = document.getElementById('manageModalImage');
                if (item.image) {
                    manageModalImage.src = item.image;
                    manageModalImage.classList.remove('hidden');
                } else {
                    manageModalImage.classList.add('hidden');
                }
                if (isViewMode) {
                    if (editButton) editButton.classList.add('hidden');
                    if (deleteButton) deleteButton.classList.add('hidden');
                } else {
                    if (editButton) editButton.classList.remove('hidden');
                    if (deleteButton) deleteButton.classList.remove('hidden');
                }
                manageModal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching item details:', error);
                showCustomAlert('Failed to load item details. Please try again.');
            });
    }

    if (myReportsContainer) {
        myReportsContainer.addEventListener('click', (e) => {
            const manageBtn = e.target.closest('.manage-btn');
            if (manageBtn) {
                const itemId = manageBtn.getAttribute('data-item-id');
                if (itemId) {
                    manageReport(itemId);
                }
            }
        });
    }

    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', () => {
            const itemId = button.getAttribute('data-item-id');
            const isViewMode = button.getAttribute('data-view-mode') === 'true';
            if (itemId) {
                viewItem(itemId, isViewMode);
            }
        });
    });

    profileBtn.addEventListener('click', () => {
        profileMenu.classList.toggle('hidden');
    });

    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        if (document.body.classList.contains('dark')) {
            darkModeIcon.classList.remove('fa-sun');
            darkModeIcon.classList.add('fa-moon');
        } else {
            darkModeIcon.classList.remove('fa-moon');
            darkModeIcon.classList.add('fa-sun');
        }
    });

    reportLostBtn.addEventListener('click', () => {
        reportModalTitle.textContent = 'Report a Lost Item';
        reportType.value = 'lost';
        document.getElementById('reportItemId').value = '';
        document.getElementById('reportItemName').value = '';
        document.getElementById('reportDescription').value = '';
        document.getElementById('reportLocation').value = '';
        document.getElementById('reportDate').value = '';
        reportModal.style.display = 'block';
    });

    reportFoundBtn.addEventListener('click', () => {
        reportModalTitle.textContent = 'Report a Found Item';
        reportType.value = 'found';
        document.getElementById('reportItemId').value = '';
        document.getElementById('reportItemName').value = '';
        document.getElementById('reportDescription').value = '';
        document.getElementById('reportLocation').value = '';
        document.getElementById('reportDate').value = '';
        reportModal.style.display = 'block';
    });

    submitReport.addEventListener('click', (e) => {
        if (!validateReportForm()) {
            e.preventDefault();
        }
    });

    closeReportModal.addEventListener('click', () => {
        reportModal.style.display = 'none';
        clearReportErrors();
    });

    document.querySelectorAll('.close').forEach(btn => {
        btn.addEventListener('click', () => {
            reportModal.style.display = 'none';
            manageModal.style.display = 'none';
            editProfileModal.style.display = 'none';
            notificationModal.style.display = 'none';
            customModal.style.display = 'none';
            clearReportErrors();
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target === reportModal) {
            reportModal.style.display = 'none';
            clearReportErrors();
        }
        if (e.target === manageModal) {
            manageModal.style.display = 'none';
        }
        if (e.target === editProfileModal) {
            editProfileModal.style.display = 'none';
        }
        if (e.target === notificationModal) {
            notificationModal.style.display = 'none';
        }
        if (e.target === customModal) {
            customModal.style.display = 'none';
        }
    });

    if (customModalOK) {
        customModalOK.addEventListener('click', () => {
            customModal.style.display = 'none';
        });
    }

    if (customModalClose) {
        customModalClose.addEventListener('click', () => {
            customModal.style.display = 'none';
        });
    }

    editProfileBtn.addEventListener('click', () => {
        editProfileModal.style.display = 'block';
        profileMenu.classList.add('hidden');
    });

    saveProfile.addEventListener('click', (e) => {
        if (!validateProfileForm()) {
            e.preventDefault();
        }
    });

    cancelEditProfile.addEventListener('click', () => {
        editProfileModal.style.display = 'none';
        document.getElementById('editNameError').classList.add('hidden');
        document.getElementById('editEmailError').classList.add('hidden');
    });

    const searchItems = debounce((searchTerm) => {
        const items = document.querySelectorAll('#recentLostItems > div, #recentFoundItems > div, #matchedItems > div, #claimStatus > div.scrollable > div');
        items.forEach(item => {
            const name = item.querySelector('h3')?.textContent.toLowerCase() || '';
            const description = item.querySelector('p')?.textContent.toLowerCase() || '';
            item.style.display = name.includes(searchTerm) || description.includes(searchTerm) ? 'block' : 'none';
        });
    }, 300);

    searchBar.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();
        clearSearch.classList.toggle('hidden', !searchTerm);
        searchItems(searchTerm);
    });

    clearSearch.addEventListener('click', () => {
        searchBar.value = '';
        clearSearch.classList.add('hidden');
        document.querySelectorAll('#recentLostItems > div, #recentFoundItems > div, #matchedItems > div, #claimStatus > div.scrollable > div').forEach(item => {
            item.style.display = 'block';
        });
    });

    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            if (targetId) {
                document.querySelector(`#${targetId}`).scrollIntoView({ behavior: 'smooth' });
            }
            if (window.innerWidth <= 768) {
                navMenu.classList.remove('active');
            }
        });
    });

    notificationNav.addEventListener('click', (e) => {
        e.preventDefault();
        notificationModal.style.display = 'block';
        if (notificationContent) {
            notificationContent.scrollTop = 0;
            notificationContent.querySelectorAll('.notification').forEach(notif => {
                notif.style.opacity = '1';
                notif.classList.remove('fade-out');
                notif.style.display = 'block';
                notif.style.visibility = 'visible';
            });
        }
        const notificationBadge = document.querySelector('.notification-badge');
        if (notificationBadge) {
            notificationBadge.style.display = 'none';
        }
    });

    closeNotificationModal.addEventListener('click', () => {
        notificationModal.style.display = 'none';
    });

   
    document.querySelectorAll('.close-notif').forEach(closeBtn => {
        closeBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const notification = e.target.closest('.notification');
            const notificationId = notification.dataset.id;
            console.log('Deleting notification:', { id: notificationId, text: notification.textContent });

            
            notification.style.transition = 'opacity 0.3s';
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
                console.log('Notification removed from UI:', notificationId);
            }, 300);

            
            if (notificationId) {
                try {
                    const response = await fetch('delete_notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `notification_id=${notificationId}`
                    });
                    const data = await response.json();
                    console.log('Delete response:', data);
                    if (!data.success) {
                        console.warn('Deletion failed:', data.message);
                        showCustomAlert('Failed to delete notification: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting notification:', error);
                    showCustomAlert('An error occurred while deleting the notification.');
                }
            } else {
                console.warn('Missing notificationId, UI removed only');
            }
        });
    });

    
    document.querySelectorAll('.notification:not(#notificationModal .notification)').forEach(notification => {
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s';
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 500); 
        }, 2000); 
    });
});
const signupBtn = document.getElementById('signupBtn');
signupBtn.addEventListener('click', (e) => {
  e.preventDefault();
  signupModal.style.display = 'flex';
});