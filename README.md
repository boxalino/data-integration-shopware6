# Boxalino Data Integration for Shopware6

## Introduction
Currently, the **data exporter layer** allows full & delta exports.
This plugin will allow the integrator to use a new feature - **Instant Update**, which will be part of a **data integration layer**
https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252149803/Data+Integration

## Setup
For setup, please follow the official documentation:
https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/392593619/Instant+Update

## Instant Update Integration
In case of an instant update data integration syncrhonization, the following documents are exported:
- [doc_product](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252149870/doc_product),
- [doc_attribute_value](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313624/doc+attribute+value) (affected categories),
- [doc_language](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280975/doc+language)

Once the documents are available, the following requests are done:
* [load request](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/415432770/Load+Request) per document
* a final [sync request](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/394559761/Sync+Request)

For integration guidelines, review the [wiki instructions](https://github.com/boxalino/rtux-integration-shopware/wiki/Instant-Update)

For more technical insights, review the [official documentation for Instant Update](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/392593619/Instant+Update)


## Full Integration
In case of a full data integration syncrhonization, the following documents are exported:
* for products: 
  - [doc_product](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252149870/doc_product),
  - [doc_attribute](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280945/doc+attribute), 
  - [doc_attribute_value](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313624/doc+attribute+value),
  - [doc_language](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280975/doc+language)

* for orders:
  - [doc_order](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313666/doc_order)
    
* for customers:
  - [doc_user](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252182638/doc+user)
    
For every data integration type (product, order, user), the following requests are done:
* [load request](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/415432770/Load+Request) per document
* a final [sync request](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/394559761/Sync+Request) per data integration type




## Contact us!

If you have any question, just contact us at support@boxalino.com
