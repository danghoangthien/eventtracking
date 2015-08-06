CREATE TABLE install_actions (id VARCHAR(255) NOT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, installed_time INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE add_to_wishlist_actions (id VARCHAR(255) NOT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, total_items INT NOT NULL, quantity INT NOT NULL, added_time INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE search_actions (id VARCHAR(255) NOT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, search_string VARCHAR(255) NOT NULL, searched_time INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE transaction_actions (id VARCHAR(255) NOT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, transacted_price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, currency VARCHAR(255) NOT NULL, transacted_time INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE actions (id VARCHAR(255) NOT NULL, device_id VARCHAR(255) DEFAULT NULL, application_id VARCHAR(255) NOT NULL, action_type INT NOT NULL, behaviour_id INT NOT NULL, happened_at INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_548F1EF94A4C7D4 ON actions (device_id);
CREATE TABLE add_to_cart_actions (id VARCHAR(255) NOT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, total_items INT NOT NULL, quantity INT NOT NULL, added_time INT NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE applications (id VARCHAR(255) NOT NULL, app_id VARCHAR(255) NOT NULL, app_name VARCHAR(255) NOT NULL, app_version VARCHAR(255) NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE in_cart_items (id VARCHAR(255) NOT NULL, cart_id VARCHAR(255) DEFAULT NULL, item_id VARCHAR(255) DEFAULT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_F149ECF31AD5CDBF ON in_cart_items (cart_id);
CREATE INDEX IDX_F149ECF3126F525E ON in_cart_items (item_id);
CREATE TABLE in_wishlist_items (id VARCHAR(255) NOT NULL, wishlist_id VARCHAR(255) DEFAULT NULL, item_id VARCHAR(255) DEFAULT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, PRIMARY KEY(id));CREATE TABLE in_wishlist_items (id VARCHAR(255) NOT NULL, cart_id VARCHAR(255) DEFAULT NULL, item_id VARCHAR(255) DEFAULT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_7C6E869FFB8E54CD ON in_wishlist_items (wishlist_id);
CREATE INDEX IDX_7C6E869F126F525E ON in_wishlist_items (item_id);
CREATE TABLE items (id VARCHAR(255) NOT NULL, application_id VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, currency VARCHAR(255) NOT NULL, metadata VARCHAR(255) NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_E11EE94D3E030ACD ON items (application_id);
CREATE TABLE transacted_items (id VARCHAR(255) NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, item_id VARCHAR(255) DEFAULT NULL, device_id VARCHAR(255) NOT NULL, application_id VARCHAR(255) NOT NULL, transacted_price DOUBLE PRECISION NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_C4F6324A2FC0CB0F ON transacted_items (transaction_id);
CREATE INDEX IDX_C4F6324A126F525E ON transacted_items (item_id);
CREATE TABLE android_devices (id VARCHAR(255) NOT NULL, advertising_id VARCHAR(255) NOT NULL, android_id VARCHAR(255) NOT NULL, imei VARCHAR(255) NOT NULL, device_brand VARCHAR(255) NOT NULL, device_model VARCHAR(255) NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE devices (id VARCHAR(255) NOT NULL, identity_id VARCHAR(255) DEFAULT NULL, platform INT NOT NULL, click_time INT NOT NULL, install_time INT NOT NULL, country_code VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, ip VARCHAR(255) NOT NULL, wifi VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, operator VARCHAR(255) NOT NULL, device_os_version VARCHAR(255) NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_11074E9AFF3ED4A8 ON devices (identity_id);
CREATE TABLE ios_devices (id VARCHAR(255) NOT NULL, idfa VARCHAR(255) NOT NULL, idfv VARCHAR(255) NOT NULL, mac VARCHAR(255) NOT NULL, device_name VARCHAR(255) NOT NULL, device_type VARCHAR(255) NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
CREATE TABLE identities (id VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, sex INT NOT NULL, birthday VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_2 VARCHAR(255) NOT NULL, email_3 VARCHAR(255) NOT NULL, facebook_id VARCHAR(255) NOT NULL, created INT NOT NULL, PRIMARY KEY(id));
ALTER TABLE install_actions ADD CONSTRAINT FK_705F314EBF396750 FOREIGN KEY (id) REFERENCES actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE add_to_wishlist_actions ADD CONSTRAINT FK_52858DCCBF396750 FOREIGN KEY (id) REFERENCES actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE search_actions ADD CONSTRAINT FK_88991E75BF396750 FOREIGN KEY (id) REFERENCES actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE transaction_actions ADD CONSTRAINT FK_73E2CD0DBF396750 FOREIGN KEY (id) REFERENCES actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE actions ADD CONSTRAINT FK_548F1EF94A4C7D4 FOREIGN KEY (device_id) REFERENCES devices (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE add_to_cart_actions ADD CONSTRAINT FK_318F8A11BF396750 FOREIGN KEY (id) REFERENCES actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE in_cart_items ADD CONSTRAINT FK_F149ECF31AD5CDBF FOREIGN KEY (cart_id) REFERENCES add_to_cart_actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE in_cart_items ADD CONSTRAINT FK_F149ECF3126F525E FOREIGN KEY (item_id) REFERENCES items (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE in_wishlist_items ADD CONSTRAINT FK_7C6E869FFB8E54CD FOREIGN KEY (wishlist_id) REFERENCES add_to_wishlist_actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE in_wishlist_items ADD CONSTRAINT FK_7C6E869F126F525E FOREIGN KEY (item_id) REFERENCES items (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE items ADD CONSTRAINT FK_E11EE94D3E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE transacted_items ADD CONSTRAINT FK_C4F6324A2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction_actions (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE transacted_items ADD CONSTRAINT FK_C4F6324A126F525E FOREIGN KEY (item_id) REFERENCES items (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE android_devices ADD CONSTRAINT FK_82570584BF396750 FOREIGN KEY (id) REFERENCES devices (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE devices ADD CONSTRAINT FK_11074E9AFF3ED4A8 FOREIGN KEY (identity_id) REFERENCES identities (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE ios_devices ADD CONSTRAINT FK_A9B1C932BF396750 FOREIGN KEY (id) REFERENCES devices (id) NOT DEFERRABLE INITIALLY IMMEDIATE;