<?php
class ControllerApiGetProduct extends Controller
{
    public function index()
    {
        $this->load->language('api/cart');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $json = array();
        $json['product'] = array();
        $json['product'] = $this->model_catalog_product->getProduct($this->request->post['product_id']);
        if ($json['product']['image']) {
            $image = $this->model_tool_image->resize($json['product']['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
        } else {
            $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
        }
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $price = $this->currency->format($this->tax->calculate($json['product']['price'], $json['product']['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $price = false;
        }
        if ((float) $json['product']['special']) {
            $special = $this->currency->format($this->tax->calculate($json['product']['special'], $json['product']['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $special = false;
        }
        if ($this->config->get('config_tax')) {
            $tax = $this->currency->format((float) $json['product']['special'] ? $json['product']['special'] : $json['product']['price'], $this->session->data['currency']);
        } else {
            $tax = false;
        }
        if ($this->config->get('config_review_status')) {
            $rating = (int) $json['product']['rating'];
        } else {
            $rating = false;
        }
        $json['product']['image'] = $image;
        $json['product']['price'] = $price;
        $json['product']['special'] = $special;
        $json['product']['rating'] = $rating;
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
}