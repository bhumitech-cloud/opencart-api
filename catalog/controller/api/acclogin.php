<?php
class ControllerApiAcclogin extends Controller {
	private $error = array();

	public function index() {
		$this->load->model('account/customer');

		$json = array();
		

		if ($this->customer->isLogged()) {
			$json['error'] = 'Account already logged in';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		} else {
			$this->load->language('account/login');


			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
				// Unset guest
				unset($this->session->data['guest']);

				// Default Shipping Address
				$this->load->model('account/address');

				if ($this->config->get('config_tax_customer') == 'payment') {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				if ($this->config->get('config_tax_customer') == 'shipping') {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				// Wishlist
				if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
					$this->load->model('account/wishlist');

					foreach ($this->session->data['wishlist'] as $key => $product_id) {
						$this->model_account_wishlist->addWishlist($product_id);

						unset($this->session->data['wishlist'][$key]);
					}
				}

				// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
				// if (isset($this->request->post['redirect']) && $this->request->post['redirect'] != $this->url->link('account/logout', '', true) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
				// 	$this->response->redirect(str_replace('&amp;', '&', $this->request->post['redirect']));
				// } else {
				// 	$this->response->redirect($this->url->link('account/account', '', true));
				// }
			}

			if (isset($this->session->data['error'])) {
				$json['error_warning'] = $this->session->data['error'];

				unset($this->session->data['error']);
			} elseif (isset($this->error['warning'])) {
				$json['error_warning'] = $this->error['warning'];
			} else {
				$json['error_warning'] = '';
			}

			// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
			// if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
			// 	$data['redirect'] = $this->request->post['redirect'];
			// } elseif (isset($this->session->data['redirect'])) {
			// 	$data['redirect'] = $this->session->data['redirect'];
			// 	unset($this->session->data['redirect']);
			// } else {
			// 	$data['redirect'] = '';
			// }

			if ($this->customer->isLogged()) {
				$json['login status'] = 'successfully logged in';
			} else {
				$json['login status'] = 'unsuccessfull';
			}
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		}
	}

	protected function validate() {
		// Check how many login attempts have been made.
		$login_info = $this->model_account_customer->getLoginAttempts($this->request->post['email']);

		if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
			$this->error['warning'] = $this->language->get('error_attempts');
		}

		// Check if customer has been approved.
		$customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

		if ($customer_info && !$customer_info['status']) {
			$this->error['warning'] = $this->language->get('error_approved');
		}

		if (!$this->error) {
			if (!$this->customer->login($this->request->post['email'], $this->request->post['password'])) {
				$this->error['warning'] = $this->language->get('error_login');

				$this->model_account_customer->addLoginAttempt($this->request->post['email']);
			} else {
				$this->model_account_customer->deleteLoginAttempts($this->request->post['email']);
			}
		}

		return !$this->error;
	}
}
