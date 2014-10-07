<?php

abstract class PhortuneController extends PhabricatorController {

  protected function loadActiveAccount(PhabricatorUser $user) {
    return PhortuneAccountQuery::loadActiveAccountForUser(
      $user,
      PhabricatorContentSource::newFromRequest($this->getRequest()));
  }

  protected function buildChargesTable(array $charges, $show_cart = true) {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $rows = array();
    foreach ($charges as $charge) {
      $cart = $charge->getCart();
      $cart_id = $cart->getID();
      $cart_uri = $this->getApplicationURI("cart/{$cart_id}/");
      $cart_href = phutil_tag(
        'a',
        array(
          'href' => $cart_uri,
        ),
        pht('Cart %d', $cart_id));

      $rows[] = array(
        $charge->getID(),
        $cart_href,
        $charge->getPaymentProviderKey(),
        $charge->getPaymentMethodPHID(),
        $charge->getAmountAsCurrency()->formatForDisplay(),
        $charge->getStatus(),
        phabricator_datetime($charge->getDateCreated(), $viewer),
      );
    }

    $charge_table = id(new AphrontTableView($rows))
      ->setHeaders(
        array(
          pht('ID'),
          pht('Cart'),
          pht('Provider'),
          pht('Method'),
          pht('Amount'),
          pht('Status'),
          pht('Created'),
        ))
      ->setColumnClasses(
        array(
          '',
          'strong',
          '',
          '',
          'wide right',
          '',
          '',
        ))
      ->setColumnVisibility(
        array(
          true,
          $show_cart,
        ));

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Charge History'));

    return id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->appendChild($charge_table);
  }

  protected function addAccountCrumb(
    $crumbs,
    PhortuneAccount $account,
    $link = true) {

    $name = pht('Account');
    $href = null;

    if ($link) {
      $href = $this->getApplicationURI($account->getID().'/');
      $crumbs->addTextCrumb($name, $href);
    } else {
      $crumbs->addTextCrumb($name);
    }
  }

  private function loadEnabledProvidersForMerchant(PhortuneMerchant $merchant) {
    $viewer = $this->getRequest()->getUser();

    $provider_configs = id(new PhortunePaymentProviderConfigQuery())
      ->setViewer($viewer)
      ->withMerchantPHIDs(array($merchant->getPHID()))
      ->execute();
    $providers = mpull($provider_configs, 'buildProvider', 'getID');

    foreach ($providers as $key => $provider) {
      if (!$provider->isEnabled()) {
        unset($providers[$key]);
      }
    }

    return $providers;
  }

  protected function loadCreatePaymentMethodProvidersForMerchant(
    PhortuneMerchant $merchant) {

    $providers = $this->loadEnabledProvidersForMerchant($merchant);
    foreach ($providers as $key => $provider) {
      if (!$provider->canCreatePaymentMethods()) {
        unset($providers[$key]);
        continue;
      }
    }

    return $providers;
  }

  protected function loadOneTimePaymentProvidersForMerchant(
    PhortuneMerchant $merchant) {

    $providers = $this->loadEnabledProvidersForMerchant($merchant);
    foreach ($providers as $key => $provider) {
      if (!$provider->canProcessOneTimePayments()) {
        unset($providers[$key]);
        continue;
      }
    }

    return $providers;
  }

}
