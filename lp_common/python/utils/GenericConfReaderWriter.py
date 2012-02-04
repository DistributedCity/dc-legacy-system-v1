import GenericConfReader, re, string

class GenericConfReaderWriter(GenericConfReader.ConfReader):
	def __init__(self, file):
		GenericConfReader.ConfReader.__init__(self, file)
		self.end_human_edit = '#### DO NOT TOUCH THIS LINE OR BELOW ###'

		self.__modified_vars = {}
	
	def write(self, file):
		writeable = 0

		conf = open(file, 'w')

		for line in self.confs:
			if not line:
				break
			bare_line = string.strip(line)

			if(bare_line == self.end_human_edit):
				writeable = 1

			if (writeable):
				is_key_val_pair = 1
				### split conf name/value
				try:
					(conf_name, conf_value) = string.split(line, self.config_separator, 1)
				except ValueError:
					is_key_val_pair = 0

				if(is_key_val_pair):
					conf_name = re.sub(GenericConfReader.illegal_chars, '_', string.strip(conf_name))
					if not self.config_case_sensative:
						conf_name = lower(conf_name)
					conf_value = string.strip(conf_value)

					if self.__modified_vars.has_key(conf_name):
						line = conf_name + ' ' + self.config_separator + ' ' + self.__modified_vars[conf_name]['value'] + '\n'
						del(self.__modified_vars[conf_name])

			conf.write(line)

		keys = self.__modified_vars.keys()

		for key in keys:
			val = self.__modified_vars[key]
			if(val['comment'] != None):
				conf.write('# ' + val['comment'] + '\n')
			conf.write( key + ' ' + self.config_separator + ' ' + val['value'] + '\n')

		conf.close()

	def set_attr(self, key, value, comment=None):
		setattr(self, key, value)
		self.__modified_vars[key] = {'value' : value, 'comment' : comment}


if __name__ == '__main__':
	conf = GenericConfReaderWriter('VERSION')
	conf.parse()

	conf.set_attr('MAJOR', '8')
	conf.write('VERSION.NEW')

